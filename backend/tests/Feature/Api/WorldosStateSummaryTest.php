<?php

namespace Tests\Feature\Api;

use App\Modules\World\Models\Multiverse;
use App\Modules\World\Models\Universe;
use App\Modules\WorldOS\Models\User;
use App\Modules\World\Models\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorldosStateSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function createUniverseWithState(): Universe
    {
        $mv = Multiverse::create(['name' => 'Test', 'slug' => 'test-mv', 'config' => []]);
        $world = World::create([
            'multiverse_id' => $mv->id,
            'name' => 'Test World',
            'slug' => 'test-world',
            'axiom' => [],
            'world_seed' => [],
            'origin' => 'generic',
            'global_tick' => 0,
        ]);

        return Universe::create([
            'world_id' => $world->id,

            'multiverse_id' => $mv->id,
            'current_tick' => 10,
            'status' => 'active',
            'state_vector' => [
                'civilization' => [
                    'economy' => ['total_surplus' => 100],
                    'discovery' => ['fitness' => 12.5, 'updated_tick' => 10],
                ],
                'knowledge_graph' => [
                    'nodes' => [['id' => 'idea_1'], ['id' => 'idea_2']],
                    'edges' => [['from' => 'idea_1', 'to' => 'idea_2']],
                    'updated_tick' => 10,
                ],
                'ideology_conversion' => ['rate_per_tick' => 0.02],
            ],
        ]);
    }

    public function test_state_summary_returns_discovery_knowledge_graph_ideology_conversion(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);
        $universe = $this->createUniverseWithState();
        $response = $this->getJson("/api/worldos/universes/{$universe->id}/state-summary");
        $response->assertOk();
        $response->assertJsonPath('universe_id', $universe->id);
        $response->assertJsonPath('current_tick', 10);
        $response->assertJsonPath('discovery.fitness', 12.5);
        $response->assertJsonPath('discovery.updated_tick', 10);
        $response->assertJsonPath('knowledge_graph.node_count', 2);
        $response->assertJsonPath('knowledge_graph.edge_count', 1);
        $response->assertJsonPath('knowledge_graph.updated_tick', 10);
        $response->assertJsonPath('ideology_conversion.rate_per_tick', 0.02);
    }
}
