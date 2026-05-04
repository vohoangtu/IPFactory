<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Orchestrator\ImplicitOrchestratorService;
use App\Services\Simulation\UniverseRuntimeService;
use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\Multiverse;
use App\Services\Simulation\OriginSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ImplicitOrchestratorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_spawn_universe_creates_universe_with_implicit_saga()
    {
        $multiverse = Multiverse::create([
            'name' => 'Test Multiverse',
            'slug' => 'test-multiverse',
            'config' => []
        ]);
        $world = World::create([
            'multiverse_id' => $multiverse->id,
            'name' => 'Test World',
            'slug' => 'test-world',
            'status' => 'active',
            'axiom' => [],
            'world_seed' => [],
            'origin' => 'generic',
            'global_tick' => 0,
        ]);

        $runtime = Mockery::mock(UniverseRuntimeService::class);
        $originSeeder = Mockery::mock(OriginSeeder::class);
        $originSeeder->shouldReceive('seed')->byDefault();
        $mutationService = Mockery::mock(\App\Services\Simulation\KernelMutationService::class);
        $mutationService->shouldReceive('ensureGenome')->once()->with(Mockery::type(Universe::class))->andReturnNull();

        $service = new ImplicitOrchestratorService($runtime, $originSeeder, $mutationService);

        $universe = $service->spawnUniverse($world);

        $this->assertEquals($world->id, $universe->world_id);

        $this->assertEquals('active', $universe->status);
    }
}
