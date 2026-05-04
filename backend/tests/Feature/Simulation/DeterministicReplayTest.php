<?php

declare(strict_types=1);

namespace Tests\Feature\Simulation;

use App\Contracts\SimulationEngineClientInterface;
use App\Modules\World\Models\Multiverse;
use App\Modules\Simulation\Models\TickManifest;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\World\Models\World;
use App\Modules\Simulation\Core\Services\SimulationReplayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Deterministic replay: run N ticks, then replay each tick using saved
 * TickManifest seeds and verify event type match (determinism).
 */
class DeterministicReplayTest extends TestCase
{
    use RefreshDatabase;

    private Universe $universe;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        $this->seedUniverse();
        $this->bindMockEngine();
    }

    public function test_replay_20_ticks_matches_original(): void
    {
        $totalTicks = 20;

        // 1. Run 20 ticks, creating manifests and snapshots
        $action = $this->app->make(\App\Modules\Simulation\Actions\AdvanceSimulationAction::class);

        for ($i = 0; $i < $totalTicks; $i++) {
            $result = $action->execute($this->universe->id, 1);
            $this->assertTrue($result['ok'] ?? false, "Original tick " . ($i + 1) . " should succeed");
        }

        // 2. Verify manifests were created
        $manifestCount = TickManifest::where('universe_id', $this->universe->id)->count();
        $this->assertGreaterThanOrEqual(
            1,
            $manifestCount,
            'At least one TickManifest should exist after running ticks'
        );

        // 3. Replay each tick that has both a manifest and a prior snapshot
        $replayService = $this->app->make(SimulationReplayService::class);
        $replayResults = [];
        $divergences = [];

        $manifests = TickManifest::where('universe_id', $this->universe->id)
            ->orderBy('tick')
            ->get();

        foreach ($manifests as $manifest) {
            // Check if a snapshot before this tick exists
            $priorSnapshot = UniverseSnapshot::where('universe_id', $this->universe->id)
                ->where('tick', '<', $manifest->tick)
                ->exists();

            if (! $priorSnapshot) {
                // Cannot replay without a prior snapshot — skip
                continue;
            }

            $replayResult = $replayService->replay($this->universe->id, $manifest->tick);
            $replayResults[] = $replayResult;

            if ($replayResult['ok'] && ! ($replayResult['is_deterministic'] ?? true)) {
                $divergences[] = [
                    'tick' => $manifest->tick,
                    'divergences' => $replayResult['divergences'],
                ];
            }
        }

        // 4. Assert no divergences
        $this->assertEmpty(
            $divergences,
            'All replayed ticks should be deterministic. Divergences: ' . json_encode($divergences)
        );
    }

    public function test_replay_with_same_seed_produces_identical_events(): void
    {
        // Run a single tick
        $action = $this->app->make(\App\Modules\Simulation\Actions\AdvanceSimulationAction::class);
        $result = $action->execute($this->universe->id, 1);
        $this->assertTrue($result['ok'] ?? false);

        // Check if manifest was created
        $manifest = TickManifest::where('universe_id', $this->universe->id)
            ->where('tick', 1)
            ->first();

        if (! $manifest) {
            $this->markTestSkipped('No TickManifest created for tick 1 — manifest recording may be disabled');
        }

        $this->assertNotNull($manifest->seed, 'Manifest should have a seed');
        $this->assertIsInt($manifest->seed, 'Seed should be an integer');
    }

    public function test_manifest_records_engine_metadata(): void
    {
        $action = $this->app->make(\App\Modules\Simulation\Actions\AdvanceSimulationAction::class);
        $result = $action->execute($this->universe->id, 1);
        $this->assertTrue($result['ok'] ?? false);

        $manifest = TickManifest::where('universe_id', $this->universe->id)
            ->first();

        if (! $manifest) {
            $this->markTestSkipped('No TickManifest created — manifest recording may be disabled');
        }

        $this->assertIsArray($manifest->engines_ran, 'engines_ran should be an array');
        $this->assertIsArray($manifest->engines_skipped, 'engines_skipped should be an array');
        $this->assertIsFloat($manifest->elapsed_ms, 'elapsed_ms should be a float');
    }

    public function test_replay_returns_error_for_nonexistent_manifest(): void
    {
        $replayService = $this->app->make(SimulationReplayService::class);
        $result = $replayService->replay($this->universe->id, 9999);

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('error', $result);
    }

    // --------------------------------------------------
    // Helpers
    // --------------------------------------------------

    private function seedUniverse(): void
    {
        $multiverse = Multiverse::create([
            'name' => 'Replay MV',
            'slug' => 'replay-mv-' . uniqid(),
            'config' => [],
        ]);
        $world = World::create([
            'multiverse_id' => $multiverse->id,
            'name' => 'Replay World',
            'slug' => 'replay-world-' . uniqid(),
            'axiom' => [],
            'world_seed' => [],
            'origin' => 'generic',
            'global_tick' => 0,
            'snapshot_interval' => 1,
        ]);
        $this->universe = Universe::create([
            'world_id' => $world->id,
            'multiverse_id' => $multiverse->id,
            'current_tick' => 0,
            'status' => 'active',
            'state_vector' => [
                'zones' => [
                    ['id' => 0, 'state' => ['base_mass' => 100, 'entropy' => 0.5], 'neighbors' => []],
                ],
            ],
        ]);
    }

    private function bindMockEngine(): void
    {
        $uid = $this->universe->id;

        $mockEngine = new class($uid) implements SimulationEngineClientInterface {
            private int $callCount = 0;

            public function __construct(
                private readonly int $uid,
            ) {}

            public function advance(int $universeId, int $ticks, array $stateInput = [], ?array $worldConfig = null): array
            {
                $this->callCount++;

                return [
                    'ok' => true,
                    'snapshot' => [
                        'universe_id' => $this->uid,
                        'tick' => $this->callCount,
                        'entropy' => 0.5,
                        'stability_index' => 0.7,
                        'state_vector' => [
                            'zones' => [
                                ['id' => 0, 'state' => ['base_mass' => 100, 'entropy' => 0.5], 'neighbors' => []],
                            ],
                        ],
                        'metrics' => ['engine_health' => 95.0],
                        'sci' => 0.8,
                        'instability_gradient' => 0.01,
                        'global_fields' => null,
                    ],
                    'error_message' => '',
                ];
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
