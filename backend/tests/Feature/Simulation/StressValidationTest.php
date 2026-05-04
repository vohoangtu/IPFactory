<?php

declare(strict_types=1);

namespace Tests\Feature\Simulation;

use App\Contracts\SimulationEngineClientInterface;
use App\Modules\World\Models\Multiverse;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use App\Modules\Simulation\Actions\AdvanceSimulationAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Stress validation: run 50 ticks through the full pipeline,
 * assert no crashes, and verify basic metrics.
 */
class StressValidationTest extends TestCase
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

    public function test_50_ticks_complete_without_crash(): void
    {
        $action = $this->app->make(AdvanceSimulationAction::class);

        $errors = [];

        for ($i = 0; $i < 50; $i++) {
            $result = $action->execute($this->universe->id, 1);

            if (! ($result['ok'] ?? false)) {
                $errors[] = [
                    'tick' => $i + 1,
                    'error' => $result['error_message'] ?? 'unknown',
                ];
            }
        }

        $this->assertEmpty($errors, 'All 50 ticks should complete without errors. Failures: ' . json_encode($errors));
    }

    public function test_stress_run_updates_universe_tick(): void
    {
        $action = $this->app->make(AdvanceSimulationAction::class);
        $ticks = 10;

        for ($i = 0; $i < $ticks; $i++) {
            $result = $action->execute($this->universe->id, 1);
            $this->assertTrue($result['ok'] ?? false, "Tick " . ($i + 1) . " should succeed");
        }

        $this->universe->refresh();
        $this->assertSame($ticks, (int) $this->universe->current_tick);
    }

    public function test_stress_run_memory_stays_bounded(): void
    {
        $action = $this->app->make(AdvanceSimulationAction::class);
        $startMemory = memory_get_usage(true);

        for ($i = 0; $i < 30; $i++) {
            $action->execute($this->universe->id, 1);
        }

        $peakMemory = memory_get_peak_usage(true);
        $memoryMB = $peakMemory / 1048576;

        // Peak memory should stay under 256MB for a simple mock-engine run
        $this->assertLessThan(
            256,
            $memoryMB,
            "Peak memory ({$memoryMB}MB) should stay under 256MB"
        );
    }

    public function test_stress_run_tick_duration_is_reasonable(): void
    {
        $action = $this->app->make(AdvanceSimulationAction::class);
        $durations = [];

        for ($i = 0; $i < 20; $i++) {
            $start = microtime(true);
            $action->execute($this->universe->id, 1);
            $durations[] = (microtime(true) - $start) * 1000;
        }

        $avgMs = array_sum($durations) / count($durations);

        // With mock engine, each tick should be very fast (< 500ms)
        $this->assertLessThan(
            500,
            $avgMs,
            "Average tick duration ({$avgMs}ms) should be under 500ms with mock engine"
        );
    }

    // --------------------------------------------------
    // Helpers
    // --------------------------------------------------

    private function seedUniverse(): void
    {
        $multiverse = Multiverse::create([
            'name' => 'Stress MV',
            'slug' => 'stress-mv-' . uniqid(),
            'config' => [],
        ]);
        $world = World::create([
            'multiverse_id' => $multiverse->id,
            'name' => 'Stress World',
            'slug' => 'stress-world-' . uniqid(),
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
                    ['id' => 1, 'state' => ['base_mass' => 80, 'entropy' => 0.3], 'neighbors' => [0]],
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
                        'entropy' => 0.5 + ($this->callCount * 0.001),
                        'stability_index' => max(0.1, 0.7 - ($this->callCount * 0.002)),
                        'state_vector' => [
                            'zones' => [
                                ['id' => 0, 'state' => ['base_mass' => 100, 'entropy' => 0.5], 'neighbors' => []],
                                ['id' => 1, 'state' => ['base_mass' => 80, 'entropy' => 0.3], 'neighbors' => [0]],
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
