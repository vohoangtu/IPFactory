<?php
declare(strict_types=1);

namespace Tests\Feature\Simulation;

use App\Modules\World\Models\Multiverse;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use App\Modules\World\Models\UniverseBridge;
use App\Simulation\SimulationKernel;
use App\Simulation\Runtime\State\WorldState;
use App\Simulation\Domain\TickContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class KernelTelemetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_kernel_collects_and_persists_telemetry(): void
    {
        // 1. Setup Data
        $multiverse = Multiverse::create(['name' => 'Test Multiverse', 'slug' => 'test']);
        $world = World::create([
            'multiverse_id' => $multiverse->id,
            'name' => 'Test World',
            'slug' => 'test-world',
            'world_seed' => [],
            'global_tick' => 0,
        ]);
        $universe = Universe::create([
            'world_id' => $world->id,
            'multiverse_id' => $multiverse->id,
            'name' => 'Source Universe',
            'current_tick' => 10,
            'status' => 'active',
            'state_vector' => ['entropy' => 0.5],
            'level' => 1,
            'epoch' => 1,
            'structural_coherence' => 1.0,
            'entropy' => 0.5,
            'fitness_score' => 0.0,
            'observation_load' => 0.0,
            'observer_bonus' => 1.0,
        ]);

        /** @var SimulationKernel $kernel */
        $kernel = app(SimulationKernel::class);
        $state = new WorldState($universe->state_vector);
        $ctx = new TickContext($universe->id, 11, 12345);

        // 2. Run Kernel
        $result = $kernel->runTick($state, $ctx);

        // 3. Verify Result
        $this->assertNotEmpty($result->engineMetrics);
        $this->assertContains('potential_field', array_map(fn($m) => $m->engineName, $result->engineMetrics));

        // 4. Verify Cache Persistence (TickMetricsService)
        $history = Cache::get('simulation:metrics:' . $universe->id);
        $this->assertNotEmpty($history);
        $this->assertEquals(11, $history[0]['tick']);
        $this->assertCount(count($result->engineMetrics), $history[0]['engines']);

        // 5. Verify API Endpoint
        \Laravel\Sanctum\Sanctum::actingAs(\App\Modules\WorldOS\Models\User::factory()->create());

        $this->getJson("/api/worldos/universes/{$universe->id}/kernel-health")
            ->assertStatus(200)
            ->assertJsonPath('health.score', fn($v) => $v <= 100);
    }

    public function test_causal_bridge_emits_multiverse_resonance_event(): void
    {
        // 1. Setup Data
        $multiverse = Multiverse::create(['name' => 'Test Multiverse', 'slug' => 'test']);
        $world = World::create([
            'multiverse_id' => $multiverse->id,
            'name' => 'Test World',
            'slug' => 'test-world',
            'world_seed' => [],
            'global_tick' => 0,
        ]);
        $source = Universe::create([
            'world_id' => $world->id,
            'multiverse_id' => $multiverse->id,
            'name' => 'Source',
            'status' => 'active',
            'state_vector' => [],
            'current_tick' => 100,
            'level' => 1,
            'epoch' => 1,
            'structural_coherence' => 1.0,
            'entropy' => 1.0,
            'fitness_score' => 0.0,
            'observation_load' => 0.0,
            'observer_bonus' => 1.0,
        ]);
        $target = Universe::create([
            'world_id' => $world->id,
            'multiverse_id' => $multiverse->id,
            'name' => 'Target',
            'status' => 'active',
            'state_vector' => [],
            'current_tick' => 0,
            'level' => 1,
            'epoch' => 1,
            'structural_coherence' => 1.0,
            'entropy' => 0.0,
            'fitness_score' => 0.0,
            'observation_load' => 0.0,
            'observer_bonus' => 1.0,
        ]);

        // Create a bridge with 100% resonance to guarantee event during test
        UniverseBridge::create([
            'source_universe_id' => $source->id,
            'target_universe_id' => $target->id,
            'bridge_type' => 'causal',
            'resonance_level' => 1.0,
            'is_active' => true,
        ]);

        /** @var SimulationKernel $kernel */
        $kernel = app(SimulationKernel::class);
        $state = new WorldState($source->state_vector);
        $ctx = new TickContext($source->id, 100, 12345);

        // 2. Run Kernel
        $result = $kernel->runTick($state, $ctx);

        // 3. Verify Resonance Event (emitted targetting $target->id)
        $resonanceEvents = array_filter($result->events, fn($e) => $e->type === 'MULTIVERSE_RESONANCE');
        $this->assertNotEmpty($resonanceEvents, 'Should emit MULTIVERSE_RESONANCE event');
        
        $event = reset($resonanceEvents);
        $this->assertEquals($target->id, $event->universeId);
        $this->assertEquals($source->id, $event->payload['source_universe_id']);

        // 4. Verify State Effect (metadata update)
        $this->assertEquals(100, $result->state->get('meta.last_resonance_tick'));
    }
}
