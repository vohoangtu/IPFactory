<?php

namespace Tests\Feature\Services\Simulation;

use App\Modules\World\Models\Multiverse;
use App\Modules\Institutions\Models\SupremeEntity;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use App\Services\Simulation\GreatPersonLegacyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GreatPersonLegacyServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

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
            'state_vector' => [],
        ]);
    }

    public function test_writes_great_person_legacy_with_zero_entities(): void
    {
        $universe = $this->createUniverse();
        $service = app(GreatPersonLegacyService::class);
        $service->writeToStateVector($universe, 100);
        $universe->refresh();
        $sv = $universe->state_vector;
        if (is_string($sv)) {
            $sv = json_decode($sv, true) ?? [];
        }
        $this->assertArrayHasKey('great_person_legacy', $sv);
        $legacy = $sv['great_person_legacy'];
        $this->assertSame(0, $legacy['supreme_entity_count']);
        $this->assertEqualsWithDelta(0.0, (float) $legacy['aggregate_power_level'], 0.001, 'aggregate_power_level');
        $this->assertEqualsWithDelta(0.5, (float) $legacy['aggregate_karma'], 0.001, 'aggregate_karma');
        $this->assertArrayHasKey('legacy_myth_actor_count', $legacy);
        $this->assertSame(100, $legacy['updated_tick']);
    }

    public function test_writes_great_person_legacy_with_one_entity(): void
    {
        $universe = $this->createUniverse();
        SupremeEntity::create([
            'universe_id' => $universe->id,
            'name' => 'Hero',
            'entity_type' => 'deity',
            'power_level' => 0.8,
            'karma' => 0.6,
            'status' => 'active',
        ]);
        $service = app(GreatPersonLegacyService::class);
        $service->writeToStateVector($universe, 50);
        $universe->refresh();
        $sv = $universe->state_vector;
        if (is_string($sv)) {
            $sv = json_decode($sv, true) ?? [];
        }
        $this->assertArrayHasKey('great_person_legacy', $sv);
        $legacy = $sv['great_person_legacy'];
        $this->assertSame(1, $legacy['supreme_entity_count']);
        $this->assertEqualsWithDelta(0.8, (float) $legacy['aggregate_power_level'], 0.001, 'aggregate_power_level');
        $this->assertEqualsWithDelta(0.6, (float) $legacy['aggregate_karma'], 0.001, 'aggregate_karma');
        $this->assertSame(50, $legacy['updated_tick']);
    }
}
