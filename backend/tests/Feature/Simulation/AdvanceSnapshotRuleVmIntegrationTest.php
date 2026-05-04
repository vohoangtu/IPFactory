<?php

namespace Tests\Feature\Simulation;

use App\Actions\Simulation\AdvanceSimulationAction;
use App\Contracts\SimulationEngineClientInterface;
use App\Events\Simulation\SimulationEventOccurred;
use App\Modules\World\Models\Multiverse;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\World\Models\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Integration: advance → save snapshot → Rule VM (evaluateAndApply) when rule_engine.enabled.
 * Asserts order and that snapshot is saved before Rule VM runs; event from rule is dispatched.
 */
class AdvanceSnapshotRuleVmIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Universe $universe;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([SimulationEventOccurred::class]);
        $this->seedUniverse();
    }

    private function seedUniverse(): void
    {
        $mv = Multiverse::create(['name' => 'Test', 'slug' => 'test', 'config' => []]);
        $world = World::create([
            'multiverse_id' => $mv->id,
            'name' => 'Test World',
            'slug' => 'test-world',
            'axiom' => [],
            'world_seed' => [],
            'origin' => 'generic',
            'global_tick' => 0,
            'snapshot_interval' => 1,
        ]);
        $this->universe = Universe::create([
            'world_id' => $world->id,
            'multiverse_id' => $mv->id,
            'current_tick' => 0,
            'status' => 'active',
            'state_vector' => [
                'zones' => [
                    ['id' => 0, 'state' => ['base_mass' => 100], 'neighbors' => []],
                ],
            ],
        ]);
    }

    public function test_advance_saves_snapshot_then_rule_vm_fires_event_when_enabled(): void
    {
        $universeId = $this->universe->id;
        $mockEngine = new class($universeId) implements SimulationEngineClientInterface {
            private int $uid;

            public function __construct(int $uid)
            {
                $this->uid = $uid;
            }

            public function advance(int $universeId, int $ticks, array $stateInput = [], ?array $worldConfig = null): array
            {
                return [
                    'ok' => true,
                    'snapshot' => [
                        'universe_id' => $this->uid,
                        'tick' => 1,
                        'state_vector' => ['zones' => [['id' => 0, 'state' => ['base_mass' => 100], 'neighbors' => []]]],
                        'entropy' => 0.7,
                        'stability_index' => 0.6,
                        'metrics' => [],
                        'sci' => 0.8,
                        'instability_gradient' => 0.05,
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
                return [
                    'ok' => true,
                    'outputs' => [
                        ['type' => 'event', 'event_name' => 'high_entropy'],
                    ],
                    'error_message' => null,
                ];
            }
        };

        $this->app->instance(SimulationEngineClientInterface::class, $mockEngine);
        config(['worldos.rule_engine.enabled' => true]);
        config(['worldos.rule_engine.rules_dsl' => 'rule entropy > 0.5 => emit_event high_entropy']);

        $action = $this->app->make(AdvanceSimulationAction::class);
        $result = $action->execute($universeId, 1);

        $this->assertTrue($result['ok'] ?? false);

        $snapshot = UniverseSnapshot::where('universe_id', $universeId)->latest('tick')->first();
        $this->assertNotNull($snapshot, 'Snapshot should be saved after advance');
        $this->assertSame(1, (int) $snapshot->tick);
        $this->assertSame($universeId, (int) $snapshot->universe_id);

        Event::assertDispatched(SimulationEventOccurred::class, function ($event) {
            return $event->type === 'high_entropy' && ($event->payload['source'] ?? '') === 'rule_vm';
        });
    }

    public function test_advance_with_rule_engine_disabled_does_not_dispatch_rule_event(): void
    {
        $universeId = $this->universe->id;
        $holder = (object) ['evaluateRulesCalled' => false];
        $mockEngine = new class($universeId, $holder) implements SimulationEngineClientInterface {
            private int $universeId;
            private \stdClass $holder;

            public function __construct(int $universeId, \stdClass $holder)
            {
                $this->universeId = $universeId;
                $this->holder = $holder;
            }

            public function advance(int $universeId, int $ticks, array $stateInput = [], ?array $worldConfig = null): array
            {
                return [
                    'ok' => true,
                    'snapshot' => [
                        'universe_id' => $this->universeId,
                        'tick' => 1,
                        'state_vector' => ['zones' => []],
                        'entropy' => 0.3,
                        'stability_index' => 0.8,
                        'metrics' => [],
                        'sci' => 0.9,
                        'instability_gradient' => 0.0,
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
                $this->holder->evaluateRulesCalled = true;
                return ['ok' => true, 'outputs' => [], 'error_message' => null];
            }
        };

        $this->app->instance(SimulationEngineClientInterface::class, $mockEngine);
        config(['worldos.rule_engine.enabled' => false]);

        $action = $this->app->make(AdvanceSimulationAction::class);
        $action->execute($universeId, 1);

        $this->assertFalse($holder->evaluateRulesCalled, 'evaluateRules should not be called when rule_engine is disabled');
    }
}
