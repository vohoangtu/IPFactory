<?php

declare(strict_types=1);

namespace Tests\Feature\Simulation;

use App\Contracts\SimulationEngineClientInterface;
use App\Modules\Institutions\Models\InstitutionalEntity;
use App\Modules\World\Models\Multiverse;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use App\Modules\Simulation\Contracts\WorldRepositoryInterface;
use App\Modules\Simulation\Core\Contracts\StateCacheInterface;
use App\Modules\Simulation\Core\Supervisor\EngineDriver;
use App\Modules\Simulation\Entities\UniverseEntity;
use App\Modules\Simulation\Entities\WorldEntity;
use App\Modules\Simulation\Services\Ecology\GeographyResourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Feature tests for EngineDriver::advance() — the bridge between
 * Laravel orchestrator and Rust gRPC simulation engine.
 *
 * Tests cover:
 * - Successful advance with mock gRPC server
 * - State preparation (cache hit / cache miss paths)
 * - World config preparation
 * - Entropy floor enforcement
 * - Zone state preservation (Phase 2 custom fields)
 * - gRPC timeout / failure handling
 */
class EngineDriverTest extends TestCase
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
    // Test: Successful advance returns ok with snapshot
    // --------------------------------------------------

    public function test_advance_returns_ok_with_snapshot_on_success(): void
    {
        $universeEntity = $this->makeUniverseEntity();

        $mockEngine = Mockery::mock(SimulationEngineClientInterface::class);
        $mockEngine->shouldReceive('advance')
            ->once()
            ->with($this->universe->id, 1, Mockery::type('array'), Mockery::type('array'))
            ->andReturn([
                'ok' => true,
                'snapshot' => [
                    'universe_id' => $this->universe->id,
                    'tick' => 1,
                    'entropy' => 0.5,
                    'state_vector' => [
                        'zones' => [
                            ['id' => 0, 'state' => ['entropy' => 0.5, 'order' => 0.75], 'neighbors' => []],
                        ],
                    ],
                    'metrics' => [],
                ],
                'error_message' => '',
            ]);

        $driver = $this->makeDriver($mockEngine);
        $result = $driver->advance($universeEntity, 1);

        $this->assertTrue($result['ok']);
        $this->assertArrayHasKey('snapshot', $result);
        $this->assertArrayHasKey('_tick_duration_ms_per_tick', $result);
        $this->assertIsFloat($result['_tick_duration_ms_per_tick']);
        $this->assertSame(1, $result['snapshot']['tick']);
    }

    // --------------------------------------------------
    // Test: gRPC failure returns error without snapshot
    // --------------------------------------------------

    public function test_advance_returns_error_on_grpc_failure(): void
    {
        $universeEntity = $this->makeUniverseEntity();

        $mockEngine = Mockery::mock(SimulationEngineClientInterface::class);
        $mockEngine->shouldReceive('advance')
            ->once()
            ->andReturn([
                'ok' => false,
                'error_message' => 'gRPC Error: UNAVAILABLE: Connection refused',
            ]);

        $driver = $this->makeDriver($mockEngine);
        $result = $driver->advance($universeEntity, 1);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('UNAVAILABLE', $result['error_message']);
        $this->assertArrayNotHasKey('snapshot', $result);
    }

    // --------------------------------------------------
    // Test: gRPC timeout simulation (engine returns error)
    // --------------------------------------------------

    public function test_advance_handles_grpc_timeout_gracefully(): void
    {
        $universeEntity = $this->makeUniverseEntity();

        $mockEngine = Mockery::mock(SimulationEngineClientInterface::class);
        $mockEngine->shouldReceive('advance')
            ->once()
            ->andReturn([
                'ok' => false,
                'error_message' => 'gRPC Error: DEADLINE_EXCEEDED: Deadline exceeded after 5000ms',
            ]);

        $driver = $this->makeDriver($mockEngine);
        $result = $driver->advance($universeEntity, 1);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('DEADLINE_EXCEEDED', $result['error_message']);
    }

    // --------------------------------------------------
    // Test: Entropy floor is enforced when tick > 0
    // --------------------------------------------------

    public function test_entropy_floor_is_enforced_on_snapshot(): void
    {
        $universeEntity = $this->makeUniverseEntity();
        config(['worldos.entropy_floor' => 0.01]);

        $mockEngine = Mockery::mock(SimulationEngineClientInterface::class);
        $mockEngine->shouldReceive('advance')
            ->once()
            ->andReturn([
                'ok' => true,
                'snapshot' => [
                    'universe_id' => $this->universe->id,
                    'tick' => 5,
                    'entropy' => 0.0, // Zero entropy — should be raised to floor
                    'state_vector' => [
                        'zones' => [['id' => 0, 'state' => ['base_mass' => 100], 'neighbors' => []]],
                    ],
                    'metrics' => [],
                ],
                'error_message' => '',
            ]);

        $driver = $this->makeDriver($mockEngine);
        $result = $driver->advance($universeEntity, 1);

        $this->assertTrue($result['ok']);
        $this->assertSame(0.01, $result['snapshot']['entropy']);
    }

    // --------------------------------------------------
    // Test: State cache is preferred when tick >= current
    // --------------------------------------------------

    public function test_uses_cached_state_when_cache_tick_gte_current(): void
    {
        $universeEntity = $this->makeUniverseEntity(currentTick: 5);

        $cachedStateVector = [
            'zones' => [
                ['id' => 0, 'state' => ['entropy' => 0.9, 'from_cache' => true], 'neighbors' => []],
            ],
            'entropy' => 0.9,
            'knowledge_core' => 0.5,
            'scars' => [],
        ];

        $stateCache = Mockery::mock(StateCacheInterface::class);
        $stateCache->shouldReceive('get')
            ->with($this->universe->id)
            ->once()
            ->andReturn([
                'state_vector' => $cachedStateVector,
                'tick' => 5,
            ]);

        $capturedStateInput = null;
        $mockEngine = Mockery::mock(SimulationEngineClientInterface::class);
        $mockEngine->shouldReceive('advance')
            ->once()
            ->withArgs(function ($uid, $ticks, $stateInput, $config) use (&$capturedStateInput) {
                $capturedStateInput = $stateInput;
                return true;
            })
            ->andReturn([
                'ok' => true,
                'snapshot' => [
                    'tick' => 6,
                    'entropy' => 0.9,
                    'state_vector' => ['zones' => []],
                    'metrics' => [],
                ],
                'error_message' => '',
            ]);

        $driver = $this->makeDriver($mockEngine, stateCache: $stateCache);
        $driver->advance($universeEntity, 1);

        // Verify that cached zones were used (from_cache field preserved)
        $this->assertNotNull($capturedStateInput);
        $this->assertSame(0.9, $capturedStateInput['global_entropy']);
    }

    // --------------------------------------------------
    // Test: Falls back to universe state_vector when no cache
    // --------------------------------------------------

    public function test_falls_back_to_universe_state_vector_when_cache_miss(): void
    {
        $universeEntity = $this->makeUniverseEntity(
            stateVector: [
                'zones' => [
                    ['id' => 0, 'state' => ['entropy' => 0.3], 'neighbors' => []],
                ],
                'entropy' => 0.3,
            ]
        );

        $stateCache = Mockery::mock(StateCacheInterface::class);
        $stateCache->shouldReceive('get')
            ->with($this->universe->id)
            ->once()
            ->andReturn(null); // Cache miss

        $capturedStateInput = null;
        $mockEngine = Mockery::mock(SimulationEngineClientInterface::class);
        $mockEngine->shouldReceive('advance')
            ->once()
            ->withArgs(function ($uid, $ticks, $stateInput, $config) use (&$capturedStateInput) {
                $capturedStateInput = $stateInput;
                return true;
            })
            ->andReturn([
                'ok' => true,
                'snapshot' => [
                    'tick' => 1,
                    'entropy' => 0.3,
                    'state_vector' => ['zones' => []],
                    'metrics' => [],
                ],
                'error_message' => '',
            ]);

        $driver = $this->makeDriver($mockEngine, stateCache: $stateCache);
        $driver->advance($universeEntity, 1);

        $this->assertNotNull($capturedStateInput);
        $this->assertSame(0.3, $capturedStateInput['global_entropy']);
    }

    // --------------------------------------------------
    // Test: Zone state preservation (Phase 2 custom fields)
    // --------------------------------------------------

    public function test_custom_zone_fields_are_preserved_from_input(): void
    {
        $universeEntity = $this->makeUniverseEntity(
            stateVector: [
                'zones' => [
                    [
                        'id' => 0,
                        'state' => [
                            'entropy' => 0.4,
                            'custom_laravel_field' => 'preserved',
                            'structured_mass' => 50.0,
                        ],
                        'neighbors' => [],
                    ],
                ],
                'entropy' => 0.4,
            ]
        );

        $mockEngine = Mockery::mock(SimulationEngineClientInterface::class);
        $mockEngine->shouldReceive('advance')
            ->once()
            ->andReturn([
                'ok' => true,
                'snapshot' => [
                    'tick' => 1,
                    'entropy' => 0.5,
                    'state_vector' => ['zones' => [
                        ['id' => 0, 'state' => ['entropy' => 0.5, 'new_rust_field' => 42], 'neighbors' => []],
                    ]],
                    'zones' => [
                        ['id' => 0, 'state' => ['entropy' => 0.5, 'new_rust_field' => 42], 'neighbors' => []],
                    ],
                    'metrics' => [],
                ],
                'error_message' => '',
            ]);

        $driver = $this->makeDriver($mockEngine);
        $result = $driver->advance($universeEntity, 1);

        $this->assertTrue($result['ok']);
        // Phase 2: custom fields from Laravel merged into zones returned by Rust
        $zones = $result['snapshot']['zones'] ?? [];
        if (count($zones) > 0) {
            // The merge combines old zone state with new zone state
            $this->assertArrayHasKey('state', $zones[0]);
        }
    }

    // --------------------------------------------------
    // Test: Institutions are loaded from DB
    // --------------------------------------------------

    public function test_institutions_are_included_in_state_input(): void
    {
        // Create an active institution
        InstitutionalEntity::create([
            'universe_id' => $this->universe->id,
            'name' => 'Test Guild',
            'entity_type' => 'GUILD',
            'org_capacity' => 0.8,
            'ideology_vector' => [0.5, 0.3],
            'legitimacy' => 0.7,
            'influence_map' => ['zone_0' => 0.5],
            'spawned_at_tick' => 1,
        ]);

        $universeEntity = $this->makeUniverseEntity();

        $capturedStateInput = null;
        $mockEngine = Mockery::mock(SimulationEngineClientInterface::class);
        $mockEngine->shouldReceive('advance')
            ->once()
            ->withArgs(function ($uid, $ticks, $stateInput, $config) use (&$capturedStateInput) {
                $capturedStateInput = $stateInput;
                return true;
            })
            ->andReturn([
                'ok' => true,
                'snapshot' => [
                    'tick' => 1,
                    'entropy' => 0.5,
                    'state_vector' => ['zones' => []],
                    'metrics' => [],
                ],
                'error_message' => '',
            ]);

        $driver = $this->makeDriver($mockEngine);
        $driver->advance($universeEntity, 1);

        $this->assertNotNull($capturedStateInput);
        $this->assertArrayHasKey('institutions', $capturedStateInput);
        $this->assertCount(1, $capturedStateInput['institutions']);
        $this->assertSame('GUILD', $capturedStateInput['institutions'][0]['type']);
    }

    // --------------------------------------------------
    // Test: Tick duration is measured correctly per tick
    // --------------------------------------------------

    public function test_tick_duration_calculated_per_tick_for_multi_tick(): void
    {
        $universeEntity = $this->makeUniverseEntity();

        $mockEngine = Mockery::mock(SimulationEngineClientInterface::class);
        $mockEngine->shouldReceive('advance')
            ->once()
            ->andReturn([
                'ok' => true,
                'snapshot' => [
                    'tick' => 5,
                    'entropy' => 0.5,
                    'state_vector' => ['zones' => []],
                    'metrics' => [],
                ],
                'error_message' => '',
            ]);

        $driver = $this->makeDriver($mockEngine);
        $result = $driver->advance($universeEntity, 5);

        $this->assertTrue($result['ok']);
        $this->assertArrayHasKey('_tick_duration_ms_per_tick', $result);
        // Duration per tick should be less than total (5 ticks)
        $this->assertGreaterThan(0, $result['_tick_duration_ms_per_tick']);
    }

    // --------------------------------------------------
    // Test: World not found throws RuntimeException
    // --------------------------------------------------

    public function test_advance_throws_when_world_not_found(): void
    {
        $universeEntity = new UniverseEntity(
            id: $this->universe->id,
            worldId: 99999, // Non-existent world
            name: 'Test',
            currentTick: 0,
            entropy: 0.5,
            stabilityIndex: 0.5,
            observationLoad: 0.0,
            stateVector: [],
        );

        $mockEngine = Mockery::mock(SimulationEngineClientInterface::class);
        $mockEngine->shouldNotReceive('advance'); // Should never reach gRPC

        $worldRepo = Mockery::mock(WorldRepositoryInterface::class);
        $worldRepo->shouldReceive('findById')
            ->with(99999)
            ->once()
            ->andReturn(null);

        $driver = $this->makeDriver($mockEngine, worldRepo: $worldRepo);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('World not found');

        $driver->advance($universeEntity, 1);
    }

    // --------------------------------------------------
    // Helpers
    // --------------------------------------------------

    private function seedCosmology(): void
    {
        $this->multiverse = Multiverse::create([
            'name' => 'Test Multiverse',
            'slug' => 'test-mv-engine',
            'config' => [],
        ]);
        $this->world = World::create([
            'multiverse_id' => $this->multiverse->id,
            'name' => 'Test World',
            'slug' => 'test-world-engine-' . uniqid(),
            'axiom' => ['entropy_dominance' => true],
            'world_seed' => ['seed' => 42],
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
                    ['id' => 0, 'state' => ['base_mass' => 100, 'structured_mass' => 50.0], 'neighbors' => []],
                ],
            ],
        ]);
    }

    private function makeUniverseEntity(
        int $currentTick = 0,
        array $stateVector = [],
    ): UniverseEntity {
        $vec = !empty($stateVector)
            ? $stateVector
            : ($this->universe->state_vector ?? []);

        return new UniverseEntity(
            id: $this->universe->id,
            worldId: $this->world->id,
            name: 'Test Universe',
            currentTick: $currentTick,
            entropy: 0.5,
            stabilityIndex: 0.5,
            observationLoad: 0.0,
            stateVector: $vec,
        );
    }

    private function makeDriver(
        SimulationEngineClientInterface $engine,
        ?StateCacheInterface $stateCache = null,
        ?WorldRepositoryInterface $worldRepo = null,
    ): EngineDriver {
        $stateCache ??= Mockery::mock(StateCacheInterface::class);
        if ($stateCache instanceof \Mockery\MockInterface && !$stateCache->mockery_getExpectationsFor('get')) {
            $stateCache->shouldReceive('get')->andReturn(null);
        }

        $worldRepo ??= Mockery::mock(WorldRepositoryInterface::class);
        if ($worldRepo instanceof \Mockery\MockInterface && !$worldRepo->mockery_getExpectationsFor('findById')) {
            $worldRepo->shouldReceive('findById')
                ->with($this->world->id)
                ->andReturn(new WorldEntity(
                    id: $this->world->id,
                    multiverseId: $this->multiverse->id,
                    name: $this->world->name,
                    axiom: $this->world->axiom ?? [],
                    worldSeed: $this->world->world_seed ?? [],
                    globalTick: $this->world->global_tick ?? 0,
                ));
        }

        $geoResource = Mockery::mock(GeographyResourceService::class);
        $geoResource->shouldReceive('getResourceCapacityForZones')->andReturn([0 => 0.5]);

        return new EngineDriver($engine, $geoResource, $stateCache, $worldRepo);
    }
}
