<?php

namespace Tests\Feature\Simulation;

use App\Modules\World\Models\Multiverse;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\UniverseBridge;
use App\Modules\World\Models\World;
use App\Services\Simulation\CollapsePropagatonService;
use App\Services\Simulation\ConvergenceScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiverseConvergenceTest extends TestCase
{
    use RefreshDatabase;

    protected $multiverse;
    protected $world;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->multiverse = Multiverse::create(['name' => 'Test Multiverse', 'slug' => 'test-mv']);
        $this->world = World::create([
            'multiverse_id' => $this->multiverse->id,
            'name' => 'Test World',
            'slug' => 'test-world-'.uniqid(),
            'world_seed' => [],
            'global_tick' => 0,
        ]);
    }

    public function test_convergence_score_persisted_after_compute()
    {
        $source = Universe::create([
            'world_id' => $this->world->id,
            'multiverse_id' => $this->multiverse->id,
            'name' => 'Source Universe',
            'status' => 'active',
            'entropy' => 0.4,
            'current_tick' => 100,
            'structural_coherence' => 0.8,
            'state_vector' => ['civilizations_count' => 2]
        ]);
        $target = Universe::create([
            'world_id' => $this->world->id,
            'multiverse_id' => $this->multiverse->id,
            'name' => 'Target Universe',
            'status' => 'active',
            'entropy' => 0.45,
            'current_tick' => 100,
            'structural_coherence' => 0.75,
            'state_vector' => ['civilizations_count' => 2]
        ]);

        $bridge = UniverseBridge::create([
            'source_universe_id' => $source->id,
            'target_universe_id' => $target->id,
            'bridge_type' => 'causal',
            'resonance_level' => 0.8,
            'convergence_score' => 0,
        ]);

        $service = new ConvergenceScoreService();
        $score = $service->computeAndSave($bridge, currentTick: 100);

        $this->assertGreaterThan(0.8, $score); // the universes are very similar
        
        $bridge->refresh();
        $this->assertEqualsWithDelta($score, $bridge->convergence_score, 0.001);
        $this->assertEquals(100, $bridge->last_synced_tick);
    }

    public function test_collapse_bleed_propagates_from_target_to_source()
    {
        // Source is stable
        $source = Universe::create([
            'world_id' => $this->world->id,
            'multiverse_id' => $this->multiverse->id,
            'name' => 'Stable Source',
            'status' => 'active',
            'entropy' => 0.2,
            'current_tick' => 50,
            'structural_coherence' => 0.9,
        ]);
        // Target is collapsing
        $target = Universe::create([
            'world_id' => $this->world->id,
            'multiverse_id' => $this->multiverse->id,
            'name' => 'Dying Target',
            'status' => 'collapsing',
            'entropy' => 0.98,
            'current_tick' => 50,
            'structural_coherence' => 0.1,
        ]);

        $bridge = UniverseBridge::create([
            'source_universe_id' => $source->id,
            'target_universe_id' => $target->id,
            'bridge_type' => 'resonance',
            'resonance_level' => 0.5,
        ]);

        $service = new CollapsePropagatonService();
        $propagations = $service->propagate($target, 50);

        $this->assertCount(1, $propagations);
        $this->assertEquals($source->id, $propagations[0]['source_universe_id']);
        $this->assertEquals(0.05, $propagations[0]['bleed_entropy']); // resonance 0.5 * 0.1
    }

    public function test_bridge_api_create_and_list()
    {
        $user = \App\Modules\WorldOS\Models\User::factory()->create();
        $this->actingAs($user);

        // Testing without auth if that's how it's set, or fake user
        $source = Universe::create([
            'world_id' => $this->world->id,
            'multiverse_id' => $this->multiverse->id,
            'name' => 'Source API',
            'status' => 'active',
            'entropy' => 0.1,
            'current_tick' => 1,
            'stability_index' => 1.0,
        ]);
        $target = Universe::create([
            'world_id' => $this->world->id,
            'multiverse_id' => $this->multiverse->id,
            'name' => 'Target API',
            'status' => 'active',
            'entropy' => 0.1,
            'current_tick' => 1,
            'stability_index' => 1.0,
        ]);

        // 1. Create a bridge
        $response = $this->postJson("/api/worldos/universes/{$source->id}/bridges", [
            'target_universe_id' => $target->id,
            'bridge_type' => 'causal',
            'resonance_level' => 0.75
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('universe_bridges', [
            'source_universe_id' => $source->id,
            'target_universe_id' => $target->id,
            'bridge_type' => 'causal',
            'resonance_level' => 0.75
        ]);

        // 2. List bridges mapping
        $responseMap = $this->getJson("/api/worldos/universes/{$source->id}/convergence-map");
        $responseMap->assertStatus(200);
        $responseMap->assertJsonFragment([
            'bridge_type' => 'causal',
            'resonance_level' => 0.75,
        ]);
    }
}

