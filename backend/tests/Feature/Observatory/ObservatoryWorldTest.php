<?php

declare(strict_types=1);

namespace Tests\Feature\Observatory;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\World\Models\DiplomaticTreaty;
use App\Modules\World\Models\Epoch;
use App\Modules\World\Models\Religion;
use App\Modules\World\Models\Technology;
use App\Modules\World\Models\Universe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ObservatoryWorldTest extends TestCase
{
    use RefreshDatabase;

    public function test_world_returns_epoch_religions_treaties_technologies(): void
    {
        $universe = Universe::factory()->create(['current_tick' => 50]);
        Epoch::create(['world_id' => $universe->world_id, 'name' => 'Bronze', 'start_tick' => 0, 'end_tick' => 30, 'status' => 'ended']);
        Epoch::create(['world_id' => $universe->world_id, 'name' => 'Iron', 'start_tick' => 31, 'status' => 'active']);
        Religion::create(['universe_id' => $universe->id, 'name' => 'Lunism', 'followers' => 40, 'spread_rate' => 0.1]);
        Religion::create(['universe_id' => $universe->id, 'name' => 'Solism', 'followers' => 120, 'spread_rate' => 0.3]);
        DiplomaticTreaty::create(['universe_id' => $universe->id, 'source_civ_id' => 1, 'target_civ_id' => 2, 'treaty_type' => 'trade', 'started_at_tick' => 10, 'is_active' => true]);
        DiplomaticTreaty::create(['universe_id' => $universe->id, 'source_civ_id' => 1, 'target_civ_id' => 3, 'treaty_type' => 'war', 'started_at_tick' => 5, 'is_active' => false]);

        $tech = Technology::create(['name' => 'Lửa', 'code' => 'fire']);
        $a1 = Actor::create(['universe_id' => $universe->id, 'name' => 'A1', 'is_alive' => true, 'archetype' => 'sage', 'traits' => []]);
        $a2 = Actor::create(['universe_id' => $universe->id, 'name' => 'A2', 'is_alive' => true, 'archetype' => 'sage', 'traits' => []]);
        DB::table('actor_technologies')->insert([
            ['actor_id' => $a1->id, 'technology_id' => $tech->id, 'level' => 0.4, 'created_at' => now(), 'updated_at' => now()],
            ['actor_id' => $a2->id, 'technology_id' => $tech->id, 'level' => 0.8, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->getJson("/api/worldos/observatory/universes/{$universe->id}/world");

        $response->assertOk()
            ->assertJsonPath('data.epoch.name', 'Iron')
            ->assertJsonPath('data.religions.0.name', 'Solism')     // followers DESC
            ->assertJsonCount(2, 'data.religions')
            ->assertJsonCount(1, 'data.treaties')                    // chỉ is_active
            ->assertJsonPath('data.treaties.0.treaty_type', 'trade')
            ->assertJsonPath('data.technologies.0.code', 'fire')
            ->assertJsonPath('data.technologies.0.adopters', 2)
            ->assertJsonPath('data.technologies.0.avg_level', 0.6);
    }

    public function test_world_without_data_returns_empty_lists_and_null_epoch(): void
    {
        $universe = Universe::factory()->create();

        $this->getJson("/api/worldos/observatory/universes/{$universe->id}/world")
            ->assertOk()
            ->assertJsonPath('data.epoch', null)
            ->assertJsonPath('data.religions', [])
            ->assertJsonPath('data.treaties', [])
            ->assertJsonPath('data.technologies', []);
    }

    public function test_world_returns_404_for_missing_universe(): void
    {
        $this->getJson('/api/worldos/observatory/universes/999999/world')->assertNotFound();
    }
}
