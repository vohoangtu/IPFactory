<?php

namespace Tests\Feature\Modules\Intelligence;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use App\Modules\World\Models\Multiverse;
use App\Modules\Intelligence\Actions\UpdateCollectiveUnconsciousAction;
use App\Events\Intelligence\CollectiveUnconsciousShifted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CollectiveUnconsciousTest extends TestCase
{
    use RefreshDatabase;

    private Universe $universe;

    protected function setUp(): void
    {
        parent::setUp();
        
        $mv = Multiverse::create(['name' => 'Psyche Test', 'slug' => 'psyche-test', 'config' => []]);
        $world = World::create([
            'multiverse_id' => $mv->id,
            'name' => 'Psyche World',
            'slug' => 'psyche-world',
            'axiom' => [],
            'world_seed' => [],
            'origin' => 'test',
            'global_tick' => 0,
        ]);

        $this->universe = Universe::create([
            'world_id' => $world->id,
            'multiverse_id' => $mv->id,
            'current_tick' => 10,
            'status' => 'active',
            'state_vector' => [],
        ]);
    }

    public function test_it_aggregates_actor_motivations_into_universe_state(): void
    {
        // Create 2 actors with different traits
        // Actor 1: High Resilience (Survival), Solidarity (Belonging)
        Actor::create([
            'universe_id' => $this->universe->id,
            'name' => 'Survivor',
            'archetype' => 'commoner',
            'traits' => ['Resilience' => 0.9, 'Solidarity' => 0.8, 'Curiosity' => 0.1],
            'is_alive' => true,
        ]);

        // Actor 2: high Curiosity (Knowledge), Ambition (Wealth)
        Actor::create([
            'universe_id' => $this->universe->id,
            'name' => 'Scholar',
            'archetype' => 'scholar',
            'traits' => ['Resilience' => 0.1, 'Solidarity' => 0.2, 'Curiosity' => 0.9, 'Pragmatism' => 0.8],
            'is_alive' => true,
        ]);

        $action = app(UpdateCollectiveUnconsciousAction::class);
        $action->execute($this->universe);

        $this->universe->refresh();
        $collective = $this->universe->state_vector['collective_unconscious'];

        $this->assertNotNull($collective);
        // Survival: (0.9 + 0.1) / 2 = 0.5
        $this->assertEquals(0.5, $collective['survival']);
        // Knowledge: (0.1 + 0.9) / 2 = 0.5
        $this->assertEquals(0.5, $collective['knowledge']);
        // Belonging: (0.8 Solidarity base + 0.2 Solidarity base) / 2 ... wait
        // getMotivationProfile: 'belonging' => ($this->traits['Solidarity'] ?? 0.5) * 0.4 + ($this->traits['Conformity'] ?? 0.5) * 0.3 + ($this->traits['Loyalty'] ?? 0.3)
        // Actor 1: 0.8*0.4 + 0.5*0.3 + 0.3 = 0.32 + 0.15 + 0.3 = 0.77
        // Actor 2: 0.2*0.4 + 0.5*0.3 + 0.3 = 0.08 + 0.15 + 0.3 = 0.53
        // Avg: (0.77 + 0.53) / 2 = 0.65
        $this->assertEquals(0.65, $collective['belonging']);
    }

    public function test_it_dispatches_event_on_significant_shift(): void
    {
        Event::fake([CollectiveUnconsciousShifted::class]);

        // Initial update
        Actor::create([
            'universe_id' => $this->universe->id,
            'name' => 'Neutral',
            'archetype' => 'commoner',
            'traits' => ['Resilience' => 0.5],
            'is_alive' => true,
        ]);

        $action = app(UpdateCollectiveUnconsciousAction::class);
        $action->execute($this->universe);

        // Second update with a new "Hero" that shifts the field
        Actor::create([
            'universe_id' => $this->universe->id,
            'name' => 'The Conqueror',
            'archetype' => 'leader',
            'traits' => ['Dominance' => 1.0, 'Pride' => 1.0], // High Power/Status
            'is_heroic' => true,
            'metrics' => ['influence' => 0.5], // High weight: 0.5 * 10 = 5.0 vs 1.0 for neutral
            'is_alive' => true,
        ]);

        $action->execute($this->universe);

        Event::assertDispatched(CollectiveUnconsciousShifted::class, function ($event) {
            return $event->universe->id === $this->universe->id &&
                   isset($event->newVector['power']) &&
                   $event->newVector['power'] > $event->oldVector['power'];
        });
    }
}
