<?php

namespace Tests\Feature;

use App\Modules\World\Models\Multiverse;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\World\Models\World;
use App\Modules\WorldOS\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorldosSimulationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        \Illuminate\Support\Facades\Event::fake([\App\Events\Simulation\UniverseSimulationPulsed::class]);
        $this->seedCosmology();
    }

    protected function seedCosmology(): void
    {
        $mv = Multiverse::create(['name' => 'Test', 'slug' => 'test', 'config' => []]);
        $world = World::create([
            'multiverse_id' => $mv->id,
            'name' => 'Test World',
            'slug' => 'test-world',
            'axiom' => [],
            'world_seed' => [],
            'origin' => 'generic',
            'global_tick' => 0,
        ]);
        $universe = Universe::create([
            'world_id' => $world->id,
            'multiverse_id' => $mv->id,
            'current_tick' => 0,
            'status' => 'active',
            'state_vector' => [
                'zones' => [
                    [
                        'id' => 0,
                        'state' => ['base_mass' => 100],
                        'neighbors' => [],
                    ]
                ]
            ]
        ]);
    }

    public function test_simulation_advance_returns_ok_and_saves_snapshot(): void
    {
        $this->withoutExceptionHandling();
        $universe = Universe::firstOrFail();
        $response = $this->postJson('/api/worldos/simulation/advance', [
            'universe_id' => $universe->id,
            'ticks' => 2,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);
        $this->assertArrayHasKey('snapshot', $response->json());

        $snapshot = UniverseSnapshot::where('universe_id', $universe->id)->latest('tick')->first();
        $this->assertNotNull($snapshot);
    }

    public function test_world_pulse_returns_ok(): void
    {
        $world = World::firstOrFail();
        $response = $this->postJson("/api/worldos/worlds/{$world->id}/pulse", [
            'ticks_per_universe' => 3,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);
        $response->assertJsonStructure(['results']);
    }

    public function test_advance_rejects_invalid_input(): void
    {
        $response = $this->postJson('/api/worldos/simulation/advance', [
            'universe_id' => 0,
            'ticks' => 1,
        ]);
        $response->assertStatus(422);
    }

    public function test_fork_creates_child_universe_and_branch_event(): void
    {
        $universe = Universe::firstOrFail();
        $countBefore = Universe::count();
        $branchEventsBefore = \App\Modules\Simulation\Models\BranchEvent::where('universe_id', $universe->id)->where('event_type', 'fork')->count();

        $response = $this->postJson("/api/worldos/universes/{$universe->id}/fork", [
            'tick' => $universe->current_tick,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);
        $response->assertJsonStructure(['child_universe_id']);

        $this->assertSame($countBefore + 1, Universe::count());
        $childId = $response->json('child_universe_id');
        $child = Universe::find($childId);
        $this->assertNotNull($child);
        $this->assertSame((int) $universe->id, (int) $child->parent_universe_id);

        $this->assertGreaterThan($branchEventsBefore, \App\Modules\Simulation\Models\BranchEvent::where('universe_id', $universe->id)->where('event_type', 'fork')->count());
    }
}
