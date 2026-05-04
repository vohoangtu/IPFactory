<?php

namespace Tests\Feature;

use App\Modules\World\Models\Multiverse;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use App\Simulation\Domain\SimulationTickResult;
use App\Simulation\Domain\TickContext;
use App\Simulation\Runtime\State\WorldState;
use App\Simulation\EffectResolver;
use App\Simulation\EngineRegistry;
use App\Simulation\NullWorldEventBus;
use App\Simulation\SimulationKernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulationKernelFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_kernel_run_tick_returns_world_state_with_real_registry(): void
    {
        $this->seedMinimalCosmology();
        $universe = Universe::firstOrFail();

        $state = new WorldState([
            'universe_id' => $universe->id,
            'tick' => 2,
            'metrics' => ['entropy' => 0.4],
            'zones' => [
                ['id' => 0, 'state' => ['order' => 0.5, 'entropy' => 0.4], 'neighbors' => [1]],
                ['id' => 1, 'state' => ['order' => 0.5, 'entropy' => 0.4], 'neighbors' => [0]],
            ],
            'fields' => [
                'innovation' => 0, 'entropy' => 0.4, 'order' => 0.5, 'myth' => 0,
                'conflict' => 0, 'ascension' => 0, 'ascension_pressure' => 0, 'collapse_pressure' => 0,
            ],
        ]);
        $ctx = new TickContext((int) ($universe->id ?? 0), 2, (int) ($universe->seed ?? 0));

        $kernel = new SimulationKernel(
            $this->app->make(EffectResolver::class),
            $this->app->make(EngineRegistry::class),
            new NullWorldEventBus()
        );
        $result = $kernel->runTick($state, $ctx);

        $this->assertInstanceOf(SimulationTickResult::class, $result);
        $this->assertInstanceOf(WorldState::class, $result->state);
        $this->assertSame($universe->id, $result->state->getUniverseId());
        $this->assertSame(2, $result->state->getTick());
    }

    private function seedMinimalCosmology(): void
    {
        $mv = Multiverse::create(['name' => 'Kernel Test', 'slug' => 'kernel-test', 'config' => []]);
        $world = World::create([
            'multiverse_id' => $mv->id,
            'name' => 'Kernel World',
            'slug' => 'kernel-world',
            'axiom' => [],
            'world_seed' => [],
            'origin' => 'generic',
            'global_tick' => 0,
        ]);
        Universe::create([
            'world_id' => $world->id,

            'multiverse_id' => $mv->id,
            'current_tick' => 0,
            'status' => 'active',
            'seed' => 12345,
            'state_vector' => ['zones' => [], 'entropy' => 0.5],
        ]);
    }
}
