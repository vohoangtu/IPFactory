<?php

declare(strict_types=1);

namespace Tests\Feature\Simulation;

use App\Modules\World\Models\DiplomaticTreaty;
use App\Modules\World\Models\Multiverse;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Engines\Social\DiplomacyEngine;
use App\Modules\Simulation\Core\Events\WorldEventType;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiplomacyEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_diplomacy_engine_expires_treaties(): void
    {
        $multiverse = Multiverse::create(['name' => 'Test Multiverse', 'slug' => 'test-mv']);
        $world = World::create([
            'name' => 'Test World',
            'slug' => 'test-world-' . uniqid(),
            'world_seed' => [],
            'global_tick' => 0,
        ]);
        $universe = Universe::create([
            'world_id' => $world->id,
            'multiverse_id' => $multiverse->id,
            'name' => 'Test Universe',
            'status' => 'active',
            'current_tick' => 1,
            'entropy' => 0.5,
        ]);

        // Treaty expires at tick 100
        $treaty = DiplomaticTreaty::create([
            'universe_id' => $universe->id,
            'source_civ_id' => 1,
            'target_civ_id' => 2,
            'treaty_type' => 'ALLIANCE',
            'started_at_tick' => 10,
            'ends_at_tick' => 100,
            'is_active' => true,
        ]);

        $state = new WorldState([
            'universe_id' => $universe->id,
            'factions' => [
                ['id' => 1, 'ideology_vector' => [0.8, 0.2, 0.5]],
                ['id' => 2, 'ideology_vector' => [0.7, 0.3, 0.5]],
                ['id' => 3, 'ideology_vector' => [0.1, 0.9, 0.1]], // Very different
            ],
        ]);

        $engine = new DiplomacyEngine();

        // Tick 90: Treaty not yet expired — alliance reduces tension
        $ctx90 = new TickContext($universe->id, 90, 42);
        $result = $engine->handle($state, $ctx90);

        $this->assertTrue(DiplomaticTreaty::find($treaty->id)->is_active);
        $this->assertEmpty($result->events);

        $tensions = $result->stateChanges[0]['diplomacy.tensions'];
        $this->assertNotEmpty($tensions);
        $this->assertTrue($tensions['1_2']['has_alliance']);
        $this->assertFalse($tensions['1_3']['has_alliance']);

        // Tick 100: Treaty expires
        $ctx100 = new TickContext($universe->id, 100, 42);
        $result100 = $engine->handle($state, $ctx100);

        // Treaty should be deactivated in DB
        $this->assertFalse(DiplomaticTreaty::find($treaty->id)->is_active);

        // TREATY_EXPIRED event emitted
        $this->assertSame(WorldEventType::TREATY_EXPIRED, $result100->events[0]['type']);
        $this->assertEquals(1, $result100->events[0]['source_civ_id']);
        $this->assertEquals(2, $result100->events[0]['target_civ_id']);

        // After expiry, alliance flag should be false for pair 1_2
        $tensions100 = $result100->stateChanges[0]['diplomacy.tensions'];
        $this->assertFalse($tensions100['1_2']['has_alliance']);
    }

    public function test_diplomacy_engine_with_no_factions_returns_empty(): void
    {
        $state = new WorldState(['universe_id' => 1], []);
        $ctx = new TickContext(1, 10, 42);

        $engine = new DiplomacyEngine();
        $result = $engine->handle($state, $ctx);

        $this->assertEmpty($result->events);
        $this->assertEmpty($result->stateChanges);
    }
}
