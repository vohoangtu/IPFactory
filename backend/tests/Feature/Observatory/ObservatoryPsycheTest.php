<?php

declare(strict_types=1);

namespace Tests\Feature\Observatory;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\Intelligence\Models\AgentDecision;
use App\Modules\World\Models\Universe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObservatoryPsycheTest extends TestCase
{
    use RefreshDatabase;

    private function makeActor(int $universeId): Actor
    {
        return Actor::create([
            'universe_id' => $universeId,
            'name' => 'Aria',
            'archetype' => 'sage',
            'traits' => [],
            'is_alive' => true,
            'life_stage' => 'adult',
            'metrics' => [
                'psych_state' => ['fear' => 0.9, 'anger' => 0.1, 'sadness' => 0.2, 'joy' => 0.3, 'stress' => 0.8, 'trust' => 0.4],
                'trait_vector' => [0.5, 0.7, 0.2],
            ],
        ]);
    }

    public function test_psyche_returns_emotions_needs_goals_and_recent_decisions(): void
    {
        $universe = Universe::factory()->create();
        $actor = $this->makeActor($universe->id);
        AgentDecision::create(['actor_id' => $actor->id, 'universe_id' => $universe->id, 'tick' => 5, 'action_type' => 'gather', 'reasoning' => 'đói', 'utility_score' => 0.4, 'confidence' => 0.6, 'traits_snapshot' => []]);
        AgentDecision::create(['actor_id' => $actor->id, 'universe_id' => $universe->id, 'tick' => 9, 'action_type' => 'flee', 'reasoning' => 'nguy hiểm', 'utility_score' => 0.9, 'confidence' => 0.8, 'traits_snapshot' => []]);

        $response = $this->getJson("/api/worldos/observatory/actors/{$actor->id}/psyche");

        $response->assertOk()
            ->assertJsonPath('data.actor.id', $actor->id)
            ->assertJsonPath('data.actor.name', 'Aria')
            ->assertJsonPath('data.emotions.fear', 0.9)
            ->assertJsonPath('data.trait_vector', [0.5, 0.7, 0.2])
            ->assertJsonPath('data.recent_decisions.0.tick', 9)
            ->assertJsonPath('data.recent_decisions.0.action_type', 'flee')
            ->assertJsonCount(2, 'data.recent_decisions');

        // fear=0.9 + stress=0.8 → need "survive" = 0.9*0.7 + 0.8*0.5 = 1.03, vượt ngưỡng 0.25 → goal đầu tiên
        $goals = $response->json('data.goals');
        $this->assertNotEmpty($goals);
        $this->assertSame('survive', $goals[0]['type']);
        $this->assertEqualsWithDelta(1.03, $response->json('data.needs.survive'), 0.001);
    }

    public function test_psyche_handles_actor_without_psych_state(): void
    {
        $universe = Universe::factory()->create();
        $actor = Actor::create(['universe_id' => $universe->id, 'name' => 'Blank', 'archetype' => 'sage', 'traits' => [], 'is_alive' => true]);

        $response = $this->getJson("/api/worldos/observatory/actors/{$actor->id}/psyche");

        // psych_state rỗng → baseline (trust 0.5): emotions đủ 6 key, goals có thể rỗng, decisions rỗng
        $response->assertOk()
            ->assertJsonPath('data.emotions.trust', 0.5)
            ->assertJsonPath('data.recent_decisions', [])
            ->assertJsonPath('data.trait_vector', []);
    }

    public function test_psyche_returns_404_for_missing_actor(): void
    {
        $this->getJson('/api/worldos/observatory/actors/999999/psyche')->assertNotFound();
    }
}
