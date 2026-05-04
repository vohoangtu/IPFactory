<?php

namespace Tests\Feature\Simulation;

use App\Modules\Narrative\Models\CulturalArtifact;
use Illuminate\Support\Facades\DB;
use App\Modules\World\Models\Universe;
use App\Simulation\Domain\TickContext;
use App\Simulation\Engines\Social\CultureEngine;
use App\Simulation\Runtime\State\WorldState;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CultureEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_culture_engine_loads_and_generates_artifacts()
    {
        $now = Carbon::now();
        $multiverse = \App\Modules\World\Models\Multiverse::create(['name' => 'Test Multiverse', 'slug' => 'test-mv']);
        $world = \App\Modules\World\Models\World::create([
            'multiverse_id' => $multiverse->id,
            'name' => 'Test World',
            'slug' => 'test-world-'.uniqid(),
            'world_seed' => [],
            'global_tick' => 0,
        ]);
        
        $universe = Universe::create([
            'world_id' => $world->id,
            'multiverse_id' => $multiverse->id,
            'name' => 'Test Universe',
            'slug' => 'test-uv-'.uniqid(),
            'updated_at' => $now,
            'created_at' => $now,
            'status' => 'active',
            'current_tick' => 1,
            'entropy' => 0.5,
        ]);

        $civ1Id = DB::table('institutional_entities')->insertGetId([
            'universe_id' => $universe->id,
            'name' => 'Civ 1',
            'entity_type' => 'CIVILIZATION',
            'spawned_at_tick' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $civ2Id = DB::table('institutional_entities')->insertGetId([
            'universe_id' => $universe->id,
            'name' => 'Civ 2',
            'entity_type' => 'CIVILIZATION',
            'spawned_at_tick' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        CulturalArtifact::create([
            'universe_id' => $universe->id,
            'civ_id' => $civ1Id,
            'name' => 'Ancient Tablet',
            'type' => 'LITERATURE',
            'power_level' => 3.5,
            'created_at_tick' => 10,
            'is_active' => true,
        ]);

        $engine = new CultureEngine();
        $state = new WorldState([
            'universe_id' => $universe->id,
            'factions' => [
                ['id' => $civ1Id, 'ideology_vector' => [0.8, 0.2, 0.5]],
                ['id' => $civ2Id, 'ideology_vector' => [0.7, 0.3, 0.5]],
            ]
        ]);
        
        // Cố tình đẩy culture_tick_interval lên 1 và xác suất random = 100% bằng cách mock mt_rand nếo cần,
        // hoặc chạy tạm với config test
        config(['worldos.testing' => true]);
        config(['worldos.intelligence.culture_tick_interval' => 1]);

        $ctx = new TickContext($universe->id, 50, 49);

        // Run engine
        $result = $engine->handle($state, $ctx);

        // Verify generated artifacts in state Changes
        $stateChanges = $result->stateChanges;
        $this->assertNotEmpty($stateChanges);
        $this->assertEquals('civilization.culture', $stateChanges[0]['path']);

        $civCultures = $stateChanges[0]['value']['civ_cultures'];
        
        // Civ 1 must have the Ancient Tablet 
        $this->assertArrayHasKey($civ1Id, $civCultures);
        $this->assertGreaterThanOrEqual(1, count($civCultures[$civ1Id]['artifacts']));
        
        // Có thể có random ngẫu nhiên sinh artifact.
        // Kiểm tra xem sự kiện mới nằm trong result events hay không
        $events = $result->events;
        foreach ($events as $e) {
            $this->assertEquals('NEW_CULTURAL_ARTIFACT', $e['type']);
            $this->assertContains($e['civ_id'], [$civ1Id, $civ2Id]);
        }
    }
}
