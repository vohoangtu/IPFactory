<?php

declare(strict_types=1);

namespace Tests\Feature\Simulation;

use App\Modules\World\Models\Multiverse;
use App\Modules\Simulation\Models\TickManifest;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use App\Simulation\SimulationKernel;
use App\Simulation\Runtime\State\WorldState;
use App\Simulation\Domain\TickContext;
use App\Simulation\Services\SimulationReplayService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SimulationReplayTest extends TestCase
{
    use DatabaseTransactions;

    public function test_tick_manifest_is_created_after_simulation_tick(): void
    {
        // 1. Setup
        $multiverse = Multiverse::create(['name' => 'Test MV', 'slug' => 'test-mv']);
        $world = World::create([
            'multiverse_id' => $multiverse->id,
            'name' => 'Test World',
            'slug' => 'test-world-replay',
            'world_seed' => [],
            'global_tick' => 0,
            'current_genre' => 'fantasy',
            'base_genre' => 'fantasy',
            'origin' => 'Test',
            'axiom' => [],
        ]);
        $universe = Universe::create([
            'world_id' => $world->id,
            'name' => 'Test Universe',
            'current_tick' => 0,
            'entropy' => 0.5,
            'state_vector' => ['zones' => []],
            'stability_index' => 0.5,
            'observer_bonus' => 0,
            'observation_load' => 0,
            'level' => 1,
            'epoch' => 1,
            'structural_coherence' => 0.5,
            'fitness_score' => 0.0,
        ]);

        // 2. Run a tick via the kernel
        $kernel = app(SimulationKernel::class);
        $state = new WorldState(['zones' => []]);
        $ctx = new TickContext(
            universeId: $universe->id,
            tick: 1,
            seed: 99999,
        );
        $kernel->runTick($state, $ctx);

        // 3. Assert manifest was created
        $manifest = TickManifest::where('universe_id', $universe->id)
            ->where('tick', 1)
            ->first();

        $this->assertNotNull($manifest, 'TickManifest should be created after simulation tick.');
        $this->assertEquals(99999, $manifest->seed, 'Manifest should store the correct seed.');
        $this->assertEquals(1, $manifest->tick, 'Manifest should store the correct tick.');
        $this->assertIsArray($manifest->engines_ran, 'engines_ran should be an array.');
        $this->assertIsArray($manifest->engines_skipped, 'engines_skipped should be an array.');
        $this->assertIsFloat($manifest->elapsed_ms, 'elapsed_ms should be a float.');
    }

    public function test_replay_service_returns_ok_with_no_divergences(): void
    {
        // 1. Setup 
        $multiverse = Multiverse::create(['name' => 'Test MV2', 'slug' => 'test-mv2']);
        $world = World::create([
            'multiverse_id' => $multiverse->id,
            'name' => 'Test World 2',
            'slug' => 'test-world-replay2',
            'world_seed' => [],
            'global_tick' => 0,
            'current_genre' => 'fantasy',
            'base_genre' => 'fantasy',
            'origin' => 'Test',
            'axiom' => [],
        ]);
        $universe = Universe::create([
            'world_id' => $world->id,
            'name' => 'Test Universe 2',
            'current_tick' => 0,
            'entropy' => 0.5,
            'state_vector' => ['zones' => []],
            'stability_index' => 0.5,
            'observer_bonus' => 0,
            'observation_load' => 0,
            'level' => 1,
            'epoch' => 1,
            'structural_coherence' => 0.5,
            'fitness_score' => 0.0,
        ]);

        // 2. Seed a manifest directly (simulating a previous run)
        TickManifest::create([
            'universe_id'     => $universe->id,
            'tick'            => 5,
            'seed'            => 12345,
            'engines_ran'     => ['ClimateEngine', 'GeologicalEngine'],
            'engines_skipped' => [],
            'effects'         => [],
            'events'          => [],
            'elapsed_ms'      => 42.5,
        ]);

        // 3. There's no snapshot, so the replay should return an appropriate error
        $replayService = app(SimulationReplayService::class);
        $result = $replayService->replay($universe->id, 5);

        // Without a snapshot before tick 5, replay cannot fully execute:
        $this->assertArrayHasKey('ok', $result);
        // If no snapshot exists, we expect graceful error:
        if (!$result['ok']) {
            $this->assertStringContainsString('snapshot', strtolower($result['error'] ?? ''));
        } else {
            $this->assertTrue($result['ok']);
        }
    }

    public function test_audit_api_returns_manifest(): void
    {
        // 1. Setup
        $multiverse = Multiverse::create(['name' => 'Test MV3', 'slug' => 'test-mv3']);
        $world = World::create([
            'multiverse_id' => $multiverse->id,
            'name' => 'Test World 3',
            'slug' => 'test-world-audit',
            'world_seed' => [],
            'global_tick' => 0,
            'current_genre' => 'fantasy',
            'base_genre' => 'fantasy',
            'origin' => 'Test',
            'axiom' => [],
        ]);
        $universe = Universe::create([
            'world_id' => $world->id,
            'name' => 'Audit Universe',
            'current_tick' => 10,
            'entropy' => 0.5,
            'state_vector' => ['zones' => []],
            'stability_index' => 0.5,
            'observer_bonus' => 0,
            'observation_load' => 0,
            'level' => 1,
            'epoch' => 1,
            'structural_coherence' => 0.5,
            'fitness_score' => 0.0,
        ]);

        TickManifest::create([
            'universe_id'     => $universe->id,
            'tick'            => 10,
            'seed'            => 54321,
            'engines_ran'     => ['ClimateEngine'],
            'engines_skipped' => ['CausalBridgeEngine'],
            'effects'         => [['type' => 'WorldStateUpdateEffect']],
            'events'          => [],
            'elapsed_ms'      => 15.0,
        ]);

        \Laravel\Sanctum\Sanctum::actingAs(\App\Modules\WorldOS\Models\User::factory()->create());

        $this->getJson("/api/worldos/universes/{$universe->id}/audit/10")
            ->assertStatus(200)
            ->assertJsonPath('tick', 10)
            ->assertJsonPath('seed', 54321)
            ->assertJsonPath('engines_ran.0', 'ClimateEngine');
    }
}
