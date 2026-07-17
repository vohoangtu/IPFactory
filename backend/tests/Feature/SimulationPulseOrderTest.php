<?php

namespace Tests\Feature;

use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\World\Models\Multiverse;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use App\Modules\WorldOS\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Verifies listener order and snapshot merge:
 * - Virtual snapshot (tick % interval !== 0) does not create a new universe_snapshots row.
 * Uses Event::fake(UniverseSimulationPulsed) so virtual snapshot is not broadcast (avoids serialization 404).
 * For saved snapshot metrics see SimulationPulseMetricsTest.
 */
class SimulationPulseOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(), ['*']);
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
            'snapshot_interval' => 2,
        ]);

        Universe::create([
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
                    ],
                ],
            ],
        ]);
    }

    public function test_virtual_tick_does_not_increase_snapshot_count(): void
    {
        $universe = Universe::firstOrFail();
        \Illuminate\Support\Facades\Event::fake([\App\Modules\Simulation\Events\UniverseSimulationPulsed::class]);
        $countBefore = UniverseSnapshot::where('universe_id', $universe->id)->count();

        $response = $this->postJson('/api/worldos/simulation/advance', [
            'universe_id' => $universe->id,
            'ticks' => 1,
        ]);
        $response->assertStatus(200);

        $countAfter = UniverseSnapshot::where('universe_id', $universe->id)->count();
        $this->assertSame($countBefore, $countAfter, 'Virtual tick (tick 1 with interval 2) must not create a new snapshot row.');
    }
}
