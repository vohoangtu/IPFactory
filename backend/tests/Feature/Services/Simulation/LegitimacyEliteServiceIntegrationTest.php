<?php

namespace Tests\Feature\Services\Simulation;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\Institutions\Models\InstitutionalEntity;
use App\Modules\World\Models\Multiverse;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use App\Services\Simulation\LegitimacyEliteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegitimacyEliteServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Config::set('worldos.intelligence.politics_tick_interval', 25);
        \Illuminate\Support\Facades\Config::set('worldos.legitimacy.elite_overproduction_threshold', 0.15);
    }

    protected function createUniverse(): Universe
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
            'current_tick' => 0,
            'status' => 'active',
            'state_vector' => ['civilization' => []],
        ]);
    }

    public function test_writes_legitimacy_aggregate_and_elite_ratio_with_institutions_and_actors(): void
    {
        $universe = $this->createUniverse();

        $actor1 = Actor::create([
            'universe_id' => $universe->id,
            'name' => 'Founder A',
            'archetype' => 'leader',
            'traits' => array_fill(0, 17, 0.5),
            'is_alive' => true,
        ]);
        $actor2 = Actor::create([
            'universe_id' => $universe->id,
            'name' => 'Citizen B',
            'archetype' => 'civilian',
            'traits' => array_fill(0, 17, 0.5),
            'is_alive' => true,
        ]);

        InstitutionalEntity::create([
            'universe_id' => $universe->id,
            'name' => 'Order of Test',
            'entity_type' => 'order',
            'founder_actor_id' => $actor1->id,
            'legitimacy' => 0.7,
            'members' => 10,
            'spawned_at_tick' => 0,
            'collapsed_at_tick' => null,
        ]);

        $service = app(LegitimacyEliteService::class);
        $service->evaluate($universe, 25);

        $universe->refresh();
        $state = is_string($universe->state_vector) ? json_decode($universe->state_vector, true) : $universe->state_vector;
        $this->assertIsArray($state);
        $politics = $state['civilization']['politics'] ?? [];
        $this->assertArrayHasKey('legitimacy_aggregate', $politics);
        $this->assertArrayHasKey('elite_ratio', $politics);
        $this->assertArrayHasKey('elite_overproduction', $politics);
        $this->assertSame(25, $politics['updated_tick'] ?? null);
        $this->assertGreaterThanOrEqual(0, $politics['legitimacy_aggregate']);
        $this->assertLessThanOrEqual(1, $politics['legitimacy_aggregate']);
    }
}
