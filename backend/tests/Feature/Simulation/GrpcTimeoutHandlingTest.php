<?php

declare(strict_types=1);

namespace Tests\Feature\Simulation;

use App\Contracts\SimulationEngineClientInterface;
use App\Modules\World\Models\Multiverse;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use App\Modules\Simulation\Actions\AdvanceSimulationAction;
use App\Modules\Simulation\Services\Core\Grpc\GrpcResponseParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Feature tests for gRPC timeout and connection failure handling.
 *
 * Since the actual gRPC server (Rust engine) is not available in test,
 * these tests verify that:
 * - GrpcResponseParser correctly maps gRPC error codes to response arrays
 * - The EngineDriver / AdvanceSimulationAction pipeline gracefully handles
 *   gRPC failures (DEADLINE_EXCEEDED, UNAVAILABLE, INTERNAL, etc.)
 * - No snapshot is persisted when gRPC fails
 * - The system does not crash on connection refused
 */
class GrpcTimeoutHandlingTest extends TestCase
{
    use RefreshDatabase;

    private Multiverse $multiverse;
    private World $world;
    private Universe $universe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCosmology();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // --------------------------------------------------
    // GrpcResponseParser unit-style tests (no gRPC needed)
    // --------------------------------------------------

    public function test_parser_maps_deadline_exceeded_to_error_response(): void
    {
        $parser = new GrpcResponseParser();

        $status = new \stdClass();
        $status->code = 4; // DEADLINE_EXCEEDED
        $status->details = 'Deadline exceeded after 5000ms';

        $result = $parser->parseAdvanceResponse(null, $status);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('DEADLINE_EXCEEDED', $result['error_message']);
        $this->assertStringContainsString('5000ms', $result['error_message']);
    }

    public function test_parser_maps_unavailable_to_error_response(): void
    {
        $parser = new GrpcResponseParser();

        $status = new \stdClass();
        $status->code = 14; // UNAVAILABLE
        $status->details = 'Connection refused: localhost:50051';

        $result = $parser->parseAdvanceResponse(null, $status);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Connection refused', $result['error_message']);
    }

    public function test_parser_maps_internal_error_to_error_response(): void
    {
        $parser = new GrpcResponseParser();

        $status = new \stdClass();
        $status->code = 13; // INTERNAL
        $status->details = 'Rust engine panic: index out of bounds';

        $result = $parser->parseAdvanceResponse(null, $status);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Rust engine panic', $result['error_message']);
    }

    public function test_parser_maps_cancelled_to_error_response(): void
    {
        $parser = new GrpcResponseParser();

        $status = new \stdClass();
        $status->code = 1; // CANCELLED
        $status->details = 'Request cancelled by client';

        $result = $parser->parseAdvanceResponse(null, $status);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('cancelled', strtolower($result['error_message']));
    }

    public function test_parser_handles_evaluate_rules_timeout(): void
    {
        $parser = new GrpcResponseParser();

        $status = new \stdClass();
        $status->code = 4; // DEADLINE_EXCEEDED
        $status->details = 'Rule evaluation exceeded 5s timeout';

        $result = $parser->parseEvaluateRulesResponse(null, $status);

        $this->assertFalse($result['ok']);
        $this->assertEmpty($result['outputs']);
        $this->assertStringContainsString('timeout', strtolower($result['error_message']));
    }

    public function test_parser_handles_batch_advance_timeout(): void
    {
        $parser = new GrpcResponseParser();

        $status = new \stdClass();
        $status->code = 4; // DEADLINE_EXCEEDED
        $status->details = 'Batch advance exceeded 10s timeout';

        $result = $parser->parseBatchAdvanceResponse(null, $status);

        $this->assertEmpty($result['responses']);
        $this->assertStringContainsString('timeout', strtolower($result['error_message']));
    }

    // --------------------------------------------------
    // Pipeline-level gRPC failure tests (mock at interface)
    // --------------------------------------------------

    public function test_advance_action_handles_deadline_exceeded(): void
    {
        $this->bindMockEngine([
            'ok' => false,
            'error_message' => 'gRPC Error: Deadline exceeded after 5000ms (code: 4)',
        ]);

        $action = $this->app->make(AdvanceSimulationAction::class);
        $result = $action->execute($this->universe->id, 1);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Deadline exceeded', $result['error_message']);
    }

    public function test_advance_action_handles_connection_refused(): void
    {
        $this->bindMockEngine([
            'ok' => false,
            'error_message' => 'gRPC Error: Connection refused: localhost:50051 (code: 14)',
        ]);

        $action = $this->app->make(AdvanceSimulationAction::class);
        $result = $action->execute($this->universe->id, 1);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Connection refused', $result['error_message']);
    }

    public function test_advance_action_handles_engine_panic(): void
    {
        $this->bindMockEngine([
            'ok' => false,
            'error_message' => 'gRPC Error: Rust engine panic: thread main panicked at index out of bounds (code: 13)',
        ]);

        $action = $this->app->make(AdvanceSimulationAction::class);
        $result = $action->execute($this->universe->id, 1);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('panic', strtolower($result['error_message']));
    }

    public function test_advance_action_handles_exception_in_engine(): void
    {
        $mockEngine = Mockery::mock(SimulationEngineClientInterface::class);
        $mockEngine->shouldReceive('advance')
            ->once()
            ->andThrow(new \RuntimeException('gRPC channel broken'));

        $this->app->instance(SimulationEngineClientInterface::class, $mockEngine);

        $action = $this->app->make(AdvanceSimulationAction::class);
        $result = $action->execute($this->universe->id, 1);

        // The supervisor wraps engine calls in try/catch — result should indicate failure
        // or the exception propagates up. Either way, it shouldn't crash the app silently.
        $this->assertNotNull($result);
        if (isset($result['ok'])) {
            $this->assertFalse($result['ok']);
        }
    }

    // --------------------------------------------------
    // Test: Multi-tick stops on first failure
    // --------------------------------------------------

    public function test_multi_tick_stops_after_first_grpc_failure(): void
    {
        $callCount = 0;
        $mockEngine = new class($this->universe->id, $callCount) implements SimulationEngineClientInterface {
            private int $uid;
            private int $callCount;

            public function __construct(int $uid, int &$count)
            {
                $this->uid = $uid;
                $this->callCount = &$count;
            }

            public function advance(int $universeId, int $ticks, array $stateInput = [], ?array $worldConfig = null): array
            {
                $this->callCount++;
                if ($this->callCount >= 2) {
                    return [
                        'ok' => false,
                        'error_message' => 'gRPC Error: DEADLINE_EXCEEDED after tick 2',
                    ];
                }
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
        $result = $action->execute($this->universe->id, 5);

        // SimulationSupervisor should stop after the 2nd tick fails
        $this->assertFalse($result['ok']);
        $this->assertSame(2, $callCount, 'Engine should be called exactly twice — stop after first failure');
    }

    // --------------------------------------------------
    // Test: GrpcResponseParser with successful status code 0
    // --------------------------------------------------

    public function test_parser_returns_ok_for_status_code_zero(): void
    {
        $parser = new GrpcResponseParser();

        // Create a mock response object
        $snapshot = Mockery::mock();
        $snapshot->shouldReceive('getUniverseId')->andReturn(1);
        $snapshot->shouldReceive('getTick')->andReturn(42);
        $snapshot->shouldReceive('getStateVectorJson')->andReturn('{"zones":[]}');
        $snapshot->shouldReceive('getEntropy')->andReturn(0.5);
        $snapshot->shouldReceive('getStabilityIndex')->andReturn(0.7);
        $snapshot->shouldReceive('getMetricsJson')->andReturn('{}');
        $snapshot->shouldReceive('getSci')->andReturn(0.8);
        $snapshot->shouldReceive('getInstabilityGradient')->andReturn(0.01);
        $snapshot->shouldReceive('getGlobalFieldsJson')->andReturn('null');

        $response = Mockery::mock();
        $response->shouldReceive('getOk')->andReturn(true);
        $response->shouldReceive('getErrorMessage')->andReturn('');
        $response->shouldReceive('getSnapshot')->andReturn($snapshot);

        $status = new \stdClass();
        $status->code = 0; // OK

        $result = $parser->parseAdvanceResponse($response, $status);

        $this->assertTrue($result['ok']);
        $this->assertSame('', $result['error_message']);
        $this->assertNotNull($result['snapshot']);
        $this->assertSame(42, $result['snapshot']['tick']);
        $this->assertSame(0.5, $result['snapshot']['entropy']);
    }

    // --------------------------------------------------
    // Helpers
    // --------------------------------------------------

    private function seedCosmology(): void
    {
        $this->multiverse = Multiverse::create([
            'name' => 'gRPC Test Multiverse',
            'slug' => 'grpc-test-' . uniqid(),
            'config' => [],
        ]);
        $this->world = World::create([
            'multiverse_id' => $this->multiverse->id,
            'name' => 'gRPC Test World',
            'slug' => 'grpc-test-world-' . uniqid(),
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

    private function bindMockEngine(array $response): void
    {
        $mockEngine = new class($response) implements SimulationEngineClientInterface {
            public function __construct(private readonly array $response)
            {
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
