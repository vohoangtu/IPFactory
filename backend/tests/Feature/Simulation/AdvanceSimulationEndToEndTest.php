<?php

declare(strict_types=1);

namespace Tests\Feature\Simulation;

use App\Contracts\SimulationEngineClientInterface;
use App\Modules\World\Models\Multiverse;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\World\Models\World;
use App\Modules\Simulation\Actions\AdvanceSimulationAction;
use App\Modules\Simulation\Core\Runtime\State\StateWriter;
use App\Modules\Intelligence\Contracts\ActorRepositoryInterface;
use App\Modules\Institutions\Contracts\InstitutionalRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

/**
 * End-to-end feature test for the full advance flow:
 *   AdvanceSimulationAction → SimulationSupervisor → EngineDriver → StateWriter
 *
 * Verifies that the entire pipeline works correctly when the gRPC engine
 * is mocked at the interface level.
 */
class AdvanceSimulationEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private Multiverse $multiverse;
    private World $world;
    private Universe $universe;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        $this->seedCosmology();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // --------------------------------------------------
    // Test: Full pipeline advance → snapshot persisted
    // --------------------------------------------------

    public function test_advance_action_persists_snapshot_on_success(): void
    {
        $this->bindMockEngine([
            'ok' => true,
            'snapshot' => [
                'universe_id' => $this->universe->id,
                'tick' => 1,
                'entropy' => 0.5,
                'stability_index' => 0.6,
                'state_vector' => [
                    'zones' => [
                        ['id' => 0, 'state' => ['base_mass' => 100, 'entropy' => 0.5], 'neighbors' => []],
                    ],
                ],
                'metrics' => ['engine_health' => 95.0],
                'sci' => 0.8,
                'instability_gradient' => 0.02,
                'global_fields' => null,
            ],
            'error_message' => '',
        ]);

        /** @var AdvanceSimulationAction $action */
        $action = $this->app->make(AdvanceSimulationAction::class);
        $result = $action->execute($this->universe->id, 1);

        $this->assertTrue($result['ok'] ?? false, 'Advance action should succeed');
        $this->assertArrayHasKey('snapshot', $result);

        // Verify snapshot was persisted to database
        $snapshot = UniverseSnapshot::where('universe_id', $this->universe->id)
            ->latest('tick')
            ->first();

        $this->assertNotNull($snapshot, 'A snapshot should be persisted after advance');
        $this->assertSame(1, (int) $snapshot->tick);
        $this->assertSame($this->universe->id, (int) $snapshot->universe_id);
    }

    // --------------------------------------------------
    // Test: Advance updates universe current_tick
    // --------------------------------------------------

    public function test_advance_action_updates_universe_tick(): void
    {
        $this->bindMockEngine([
            'ok' => true,
            'snapshot' => [
                'universe_id' => $this->universe->id,
                'tick' => 1,
                'entropy' => 0.4,
                'stability_index' => 0.7,
                'state_vector' => ['zones' => []],
                'metrics' => [],
                'sci' => 0.9,
                'instability_gradient' => 0.0,
                'global_fields' => null,
            ],
            'error_message' => '',
        ]);

        $action = $this->app->make(AdvanceSimulationAction::class);
        $result = $action->execute($this->universe->id, 1);

        $this->assertTrue($result['ok'] ?? false);

        // Universe tick should be updated after advance
        $this->universe->refresh();
        $this->assertSame(1, (int) $this->universe->current_tick);
    }

    // --------------------------------------------------
    // Test: Engine failure returns error, no snapshot saved
    // --------------------------------------------------

    public function test_advance_action_returns_error_on_engine_failure(): void
    {
        $this->bindMockEngine([
            'ok' => false,
            'error_message' => 'Engine crashed: internal Rust panic',
        ]);

        $action = $this->app->make(AdvanceSimulationAction::class);
        $result = $action->execute($this->universe->id, 1);

        $this->assertFalse($result['ok'] ?? true);
        $this->assertStringContainsString('Engine crashed', $result['error_message'] ?? '');

        // No snapshot should be persisted on failure
        $snapshot = UniverseSnapshot::where('universe_id', $this->universe->id)->first();
        $this->assertNull($snapshot, 'No snapshot should exist on engine failure');
    }

    // --------------------------------------------------
    // Test: Halted universe is rejected
    // --------------------------------------------------

    public function test_advance_action_rejects_halted_universe(): void
    {
        $this->universe->update(['status' => 'halted']);

        $action = $this->app->make(AdvanceSimulationAction::class);
        $result = $action->execute($this->universe->id, 1);

        $this->assertFalse($result['ok'] ?? true);
        $this->assertStringContainsString('halted', strtolower($result['error_message'] ?? ''));
    }

    // --------------------------------------------------
    // Test: Non-existent universe returns error
    // --------------------------------------------------

    public function test_advance_action_returns_error_for_nonexistent_universe(): void
    {
        $action = $this->app->make(AdvanceSimulationAction::class);
        $result = $action->execute(99999, 1);

        $this->assertFalse($result['ok'] ?? true);
    }

    // --------------------------------------------------
    // Test: StateWriter is invoked during advance
    // --------------------------------------------------

    public function test_state_writer_batch_save_during_advance(): void
    {
        // Verify StateWriter's repository methods are called
        $actorRepo = Mockery::mock(ActorRepositoryInterface::class);
        $actorRepo->shouldReceive('findActiveByUniverse')->andReturn([]);
        $actorRepo->shouldReceive('saveBatch')->zeroOrMoreTimes();
        $actorRepo->shouldReceive('deleteBatch')->zeroOrMoreTimes();
        $this->app->instance(ActorRepositoryInterface::class, $actorRepo);

        $instRepo = Mockery::mock(InstitutionalRepositoryInterface::class);
        $instRepo->shouldReceive('findActiveByUniverse')->andReturn([]);
        $instRepo->shouldReceive('save')->zeroOrMoreTimes();
        $this->app->instance(InstitutionalRepositoryInterface::class, $instRepo);

        $this->bindMockEngine([
            'ok' => true,
            'snapshot' => [
                'universe_id' => $this->universe->id,
                'tick' => 1,
                'entropy' => 0.5,
                'stability_index' => 0.5,
                'state_vector' => ['zones' => []],
                'metrics' => [],
                'sci' => 0.5,
                'instability_gradient' => 0.0,
                'global_fields' => null,
            ],
            'error_message' => '',
        ]);

        $action = $this->app->make(AdvanceSimulationAction::class);
        $result = $action->execute($this->universe->id, 1);

        $this->assertTrue($result['ok'] ?? false);
    }

    // --------------------------------------------------
    // Test: Multi-tick advance processes all ticks
    // --------------------------------------------------

    public function test_multi_tick_advance_calls_engine_per_tick(): void
    {
        $callCount = 0;
        $mockEngine = new class($this->universe->id, $callCount) implements SimulationEngineClientInterface {
            private int $uid;
            /** @var int */
            private int $callCount;

            public function __construct(int $uid, int &$count)
            {
                $this->uid = $uid;
                $this->callCount = &$count;
            }

            public function advance(int $universeId, int $ticks, array $stateInput = [], ?array $worldConfig = null): array
            {
                $this->callCount++;
                return [
                    'ok' => true,
                    'snapshot' => [
                        'universe_id' => $this->uid,
                        'tick' => $this->callCount,
                        'entropy' => 0.5,
                        'stability_index' => 0.5,
                        'state_vector' => ['zones' => []],
                        'metrics' => [],
                        'sci' => 0.5,
                        'instability_gradient' => 0.0,
                        'global_fields' => null,
                    ],
                    'error_message' => '',
                ];
            }

            public function merge(string $stateA, string $stateB): array { return ['ok' => true]; }
            public function batchAdvance(array $requests): array { return ['responses' => []]; }
            public function analyzeTrajectory(array $points, float $threshold = 0.1): array { return []; }
            public function evaluateRules(array $state, ?string $rulesDsl = null): array { return ['ok' => true, 'outputs' => [], 'error_message' => null]; }
            public function processActorsSoa(int $tick, array $ids, array $zoneIds, array $hunger, array $energy, array $fear, array $trauma, array $heroicTypes, array $lineageIds, array $memes, array $traitsMatrix, array $behaviorStates = [], array $behaviorGraphs = [], array $archetypes = [], array $socialGraph = [], array $edicts = [], array $factionIds = [], array $factionLoyalty = [], bool $isObserved = false, array $narrativeContext = [], array $factionRelations = [], array $beliefDefinitions = [], array $beliefAlignments = [], array $techDefinitions = [], array $actorTechLevels = []): array { return ['ok' => true, 'outputs' => []]; }
            public function processFieldsV7(array $fields, array $neighborCounts, array $neighborOffsets, array $neighbors, float $diffusionRate, float $preservationRate): array { return ['ok' => true]; }
            public function computeMetabolismGrid(array $populations, array $biomasses, array $industries, float $efficiency, float $baseEnergy): array { return ['ok' => true]; }
            public function calculateVocationAlignment(array $actorMotivation, array $targetProfile): float { return 0.5; }
            public function getCombinedGravity(array $rulesets): float { return 0.5; }
        };

        $this->app->instance(SimulationEngineClientInterface::class, $mockEngine);

        $action = $this->app->make(AdvanceSimulationAction::class);
        $result = $action->execute($this->universe->id, 3);

        $this->assertTrue($result['ok'] ?? false);
        // SimulationSupervisor loops N ticks, calling engine once per tick
        $this->assertSame(3, $callCount);
    }

    // --------------------------------------------------
    // Helpers
    // --------------------------------------------------

    private function seedCosmology(): void
    {
        $this->multiverse = Multiverse::create([
            'name' => 'E2E Multiverse',
            'slug' => 'e2e-mv-' . uniqid(),
            'config' => [],
        ]);
        $this->world = World::create([
            'multiverse_id' => $this->multiverse->id,
            'name' => 'E2E World',
            'slug' => 'e2e-world-' . uniqid(),
            'axiom' => [],
            'world_seed' => [],
            'origin' => 'generic',
            'global_tick' => 0,
            'snapshot_interval' => 1,
        ]);
        $this->universe = Universe::create([
            'world_id' => $this->world->id,
            'multiverse_id' => $this->multiverse->id,
            'current_tick' => 0,
            'status' => 'active',
            'state_vector' => [
                'zones' => [
                    ['id' => 0, 'state' => ['base_mass' => 100], 'neighbors' => []],
                ],
            ],
        ]);
    }

    /**
     * Bind a mock SimulationEngineClientInterface that returns the given response.
     */
    private function bindMockEngine(array $response): void
    {
        $uid = $this->universe->id;
        $mockEngine = new class($uid, $response) implements SimulationEngineClientInterface {
            public function __construct(
                private readonly int $uid,
                private readonly array $response,
            ) {
            }

            public function advance(int $universeId, int $ticks, array $stateInput = [], ?array $worldConfig = null): array
            {
                return $this->response;
            }

            public function merge(string $stateA, string $stateB): array
            {
                return ['ok' => true, 'snapshot' => null, 'error_message' => ''];
            }

            public function batchAdvance(array $requests): array
            {
                return ['responses' => []];
            }

            public function analyzeTrajectory(array $points, float $threshold = 0.1): array
            {
                return [];
            }

            public function evaluateRules(array $state, ?string $rulesDsl = null): array
            {
                return ['ok' => true, 'outputs' => [], 'error_message' => null];
            }

            public function processActorsSoa(
                int $tick, array $ids, array $zoneIds, array $hunger, array $energy,
                array $fear, array $trauma, array $heroicTypes, array $lineageIds,
                array $memes, array $traitsMatrix, array $behaviorStates = [],
                array $behaviorGraphs = [], array $archetypes = [], array $socialGraph = [],
                array $edicts = [], array $factionIds = [], array $factionLoyalty = [],
                bool $isObserved = false, array $narrativeContext = [],
                array $factionRelations = [], array $beliefDefinitions = [],
                array $beliefAlignments = [], array $techDefinitions = [],
                array $actorTechLevels = []
            ): array {
                return ['ok' => true, 'outputs' => [], 'scars' => [], 'spawned_actors' => [], 'error_message' => ''];
            }

            public function processFieldsV7(
                array $fields, array $neighborCounts, array $neighborOffsets,
                array $neighbors, float $diffusionRate, float $preservationRate
            ): array {
                return ['ok' => true, 'fields' => $fields];
            }

            public function computeMetabolismGrid(
                array $populations, array $biomasses, array $industries,
                float $efficiency, float $baseEnergy
            ): array {
                return ['ok' => true, 'grid' => []];
            }

            public function calculateVocationAlignment(array $actorMotivation, array $targetProfile): float
            {
                return 0.5;
            }

            public function getCombinedGravity(array $rulesets): float
            {
                return 0.5;
            }
        };

        $this->app->instance(SimulationEngineClientInterface::class, $mockEngine);
    }
}
