<?php

namespace Tests\Feature\Services\Simulation;

use App\Modules\World\Models\Multiverse;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use App\Services\Simulation\ActorCognitiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActorCognitiveServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function createUniverse(): Universe
    {
        $mv = Multiverse::create(['name' => 'Test', 'slug' => 'test-cog', 'config' => []]);
        $world = World::create([
            'multiverse_id' => $mv->id,
            'name' => 'Test World',
            'slug' => 'test-world-cog',
            'axiom' => [],
            'world_seed' => [],
            'origin' => 'generic',
            'global_tick' => 0,
        ]);

        return Universe::create([
            'world_id' => $world->id,

            'multiverse_id' => $mv->id,
            'current_tick' => 0,
            'status' => 'active',
            'state_vector' => [],
        ]);
    }

    public function test_compute_cognitive_aggregate_includes_mental_state_perception_state_and_biases(): void
    {
        $universe = $this->createUniverse();

        $state = [
            'entropy' => 0.4,
            'stability_index' => 0.6,
            'cultural_coherence' => 0.5,
            'anomaly_events_count' => 2,
            'fields' => ['meaning' => 0.4, 'knowledge' => 0.5],
            'cognitive_aggregate' => ['hardtech_hint' => 0.4],
        ];

        $service = app(ActorCognitiveService::class);
        $cognitive = $service->computeCognitiveAggregate($universe, $state);

        $this->assertArrayHasKey('mental_state', $cognitive);
        $this->assertArrayHasKey('beliefs', $cognitive['mental_state']);
        $this->assertArrayHasKey('goals', $cognitive['mental_state']);
        $this->assertArrayHasKey('emotions', $cognitive['mental_state']);
        $this->assertArrayHasKey('fear', $cognitive['mental_state']['emotions']);
        $this->assertArrayHasKey('anger', $cognitive['mental_state']['emotions']);
        $this->assertArrayHasKey('hope', $cognitive['mental_state']['emotions']);
        $this->assertArrayHasKey('pride', $cognitive['mental_state']['emotions']);

        $this->assertArrayHasKey('perception_state', $cognitive);
        $this->assertArrayHasKey('information_accuracy', $cognitive['perception_state']);
        $this->assertArrayHasKey('rumors', $cognitive['perception_state']);
        $this->assertGreaterThanOrEqual(0.2, $cognitive['perception_state']['information_accuracy']);
        $this->assertLessThanOrEqual(1.0, $cognitive['perception_state']['information_accuracy']);

        $this->assertArrayHasKey('cognitive_biases', $cognitive);
        $this->assertArrayHasKey('confirmation_bias', $cognitive['cognitive_biases']);
        $this->assertArrayHasKey('loss_aversion', $cognitive['cognitive_biases']);
        $this->assertArrayHasKey('status_quo_bias', $cognitive['cognitive_biases']);
        $this->assertArrayHasKey('authority_bias', $cognitive['cognitive_biases']);
        foreach ($cognitive['cognitive_biases'] as $value) {
            $this->assertGreaterThanOrEqual(0, $value);
            $this->assertLessThanOrEqual(1, $value);
        }
    }
}
