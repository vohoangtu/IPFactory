<?php

declare(strict_types=1);

namespace Tests\Feature\Observatory;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\World\Models\Universe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObservatoryCivilizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_civilization_returns_persisted_metrics_and_complexity(): void
    {
        $universe = Universe::factory()->create(['entropy' => 0.42, 'structural_coherence' => 0.9, 'current_tick' => 33]);
        UniverseSnapshot::factory()->create(['universe_id' => $universe->id, 'tick' => 30, 'stability_index' => 0.7, 'metrics' => ['population' => 12]]);
        UniverseSnapshot::factory()->create(['universe_id' => $universe->id, 'tick' => 33, 'stability_index' => 0.66, 'metrics' => ['population' => 15]]);
        Actor::create(['universe_id' => $universe->id, 'name' => 'A1', 'is_alive' => true, 'archetype' => 'sage', 'traits' => []]);
        Actor::create(['universe_id' => $universe->id, 'name' => 'A2', 'is_alive' => false, 'archetype' => 'sage', 'traits' => []]);

        $response = $this->getJson("/api/worldos/observatory/universes/{$universe->id}/civilization");

        $response->assertOk()
            ->assertJsonPath('data.universe_id', $universe->id)
            ->assertJsonPath('data.current_tick', 33)
            ->assertJsonPath('data.metrics.entropy', 0.42)
            ->assertJsonPath('data.metrics.stability_index', 0.66)   // snapshot mới nhất theo tick
            ->assertJsonPath('data.metrics.structural_coherence', 0.9)
            ->assertJsonPath('data.complexity.actor_count', 2)
            ->assertJsonPath('data.complexity.living_actor_count', 1)
            ->assertJsonPath('data.complexity.supreme_entity_count', 0)
            ->assertJsonPath('data.snapshot.tick', 33)
            ->assertJsonPath('data.snapshot.metrics.population', 15);
    }

    public function test_civilization_without_snapshot_returns_null_snapshot(): void
    {
        $universe = Universe::factory()->create();

        $response = $this->getJson("/api/worldos/observatory/universes/{$universe->id}/civilization");

        $response->assertOk()
            ->assertJsonPath('data.snapshot', null)
            ->assertJsonPath('data.metrics.stability_index', null);
    }

    public function test_civilization_returns_404_for_missing_universe(): void
    {
        $this->getJson('/api/worldos/observatory/universes/999999/civilization')->assertNotFound();
    }
}
