<?php

namespace Tests\Feature\Modules\Narrative;

use App\Contracts\LlmNarrativeClientInterface;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Narrative\Services\NarrativeEngine;
use App\Modules\Simulation\Entities\UniverseEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class NarrativeEngineV2Test extends TestCase
{
    use RefreshDatabase;

    public function test_narrative_v2_pipeline_executes_successfully()
    {
        // 1. Setup Universe and Snapshot
        $universe = Universe::factory()->create([
            'entropy' => 0.5,
            'stability_index' => 0.5
        ]);
        
        $snapshot = UniverseSnapshot::create([
            'universe_id' => $universe->id,
            'tick' => 1,
            'state_vector' => ['test' => 123]
        ]);

        $universeEntity = new UniverseEntity(
            id: $universe->id,
            worldId: $universe->world_id ?? 1,
            name: $universe->name,
            currentTick: 1,
            entropy: 0.5,
            stabilityIndex: 0.5,
            observationLoad: 0.0,
            stateVector: []
        );

        // 2. Mock LLM Client
        $mockLlm = Mockery::mock(LlmNarrativeClientInterface::class);
        $mockLlm->shouldReceive('generate')->once()->andReturn(json_encode([
            'summary' => 'A new era begins.',
            'tension' => 'low',
            'direction' => 'growth',
            'key_factors' => ['hope', 'unity'],
            'omens' => ['The sky clears.']
        ]));

        $this->app->instance(LlmNarrativeClientInterface::class, $mockLlm);

        // 3. Execute Pulse
        $engine = app(NarrativeEngine::class);
        $engine->pulse($universeEntity, $snapshot);

        // 4. Assertions
        $this->assertDatabaseHas('chronicles', [
            'universe_id' => $universe->id,
            'from_tick' => 1,
            'content' => 'A new era begins.'
        ]);

        // Check mutation (low tension -> -0.005 entropy, growth -> +0.015 stability)
        // 0.5 - 0.005 = 0.495
        // 0.5 + 0.015 = 0.515
        $this->assertEquals(0.495, $universeEntity->entropy);
        $this->assertEquals(0.515, $universeEntity->stabilityIndex);
    }
}
