<?php

namespace Tests\Feature\Simulation;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\World\Models\Multiverse;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\WorldOS\Models\User;
use App\Modules\World\Models\World;
use App\Events\Simulation\UniverseSimulationPulsed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CollectiveUnconsciousRuntimeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function seedCosmology(): array
    {
        $mv = Multiverse::create(['name' => 'Psyche Test', 'slug' => 'psyche-test', 'config' => []]);

        $world = World::create([
            'multiverse_id' => $mv->id,
            'name' => 'Psyche World',
            'slug' => 'psyche-world',
            'axiom' => [],
            'world_seed' => [],
            'origin' => 'test',
            'global_tick' => 0,
            'snapshot_interval' => 1,
        ]);

        return [$mv, $world];
    }

    public function test_runtime_tick_persists_collective_unconscious_into_snapshot_final(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);
        Event::fake([UniverseSimulationPulsed::class]);

        [$mv, $world] = $this->seedCosmology();

        // Tạo universe sao cho tick runtime nhận được là bội của 5.
        // Stub engine: runtime tick = universe.current_tick + ticks
        // => chọn current_tick = 9 và ticks=1 => runtime tick = 10, chạy CollectiveUnconsciousSystem.
        $universe = Universe::create([
            'world_id' => $world->id,
            'multiverse_id' => $mv->id,
            'current_tick' => 9,
            'status' => 'active',
            'state_vector' => [],
            'entropy' => 0.5,
            'structural_coherence' => 1.0,
        ]);

        Actor::create([
            'universe_id' => $universe->id,
            'name' => 'Neutral',
            'archetype' => 'Unknown',
            // Để tránh crash runtime do CognitiveDynamicsEngine chưa khớp schema SocialField,
            // giữ traits trống. CollectiveUnconsciousService sẽ dùng default values cho 8D.
            'traits' => [],
            'metrics' => ['influence' => 1.0],
            'is_alive' => true,
        ]);

        $response = $this->postJson('/api/worldos/simulation/advance', [
            'universe_id' => $universe->id,
            'ticks' => 1,
        ]);
        $response->assertStatus(200);

        $snapshot = UniverseSnapshot::where('universe_id', $universe->id)
            ->orderByDesc('tick')
            ->first();

        $this->assertNotNull($snapshot, 'Advance must create at least one snapshot row.');

        $stateVector = $snapshot->state_vector ?? [];
        $this->assertIsArray($stateVector);
        $this->assertArrayNotHasKey('_hologram', $stateVector, 'Snapshot final phải canonical, không còn hologram wrapper.');
        $this->assertArrayHasKey('collective_unconscious', $stateVector, 'collective_unconscious must be persisted into snapshot final state_vector.');

        $collective = $stateVector['collective_unconscious'];
        $this->assertIsArray($collective);
        $this->assertArrayHasKey('power', $collective);
        $this->assertArrayHasKey('knowledge', $collective);
        $this->assertArrayHasKey('belonging', $collective);
    }

    public function test_collective_unconscious_mutation_does_not_change_when_below_threshold(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);
        Event::fake([UniverseSimulationPulsed::class]);

        [$mv, $world] = $this->seedCosmology();

        // Run 6 ticks: 9 -> 15 so CollectiveUnconsciousSystem executes at tick 10 and 15.
        $universe = Universe::create([
            'world_id' => $world->id,
            'multiverse_id' => $mv->id,
            'current_tick' => 9,
            'status' => 'active',
            'state_vector' => [],
            'entropy' => 0.5,
            'structural_coherence' => 1.0,
        ]);

        Actor::create([
            'universe_id' => $universe->id,
            'name' => 'Neutral',
            'archetype' => 'Unknown',
            // Minimal traits to avoid unrelated cognitive dynamics crashes.
            'traits' => [],
            'metrics' => ['influence' => 1.0],
            'is_alive' => true,
        ]);

        $response = $this->postJson('/api/worldos/simulation/advance', [
            'universe_id' => $universe->id,
            'ticks' => 6,
        ]);
        $response->assertStatus(200);

        $snap10 = UniverseSnapshot::where('universe_id', $universe->id)->where('tick', 10)->first();
        $snap15 = UniverseSnapshot::where('universe_id', $universe->id)->where('tick', 15)->first();

        $this->assertNotNull($snap10, 'Must have snapshot row for tick 10.');
        $this->assertNotNull($snap15, 'Must have snapshot row for tick 15.');

        $p10 = $snap10->state_vector['collective_unconscious']['power'] ?? null;
        $p15 = $snap15->state_vector['collective_unconscious']['power'] ?? null;

        $this->assertNotNull($p10);
        $this->assertNotNull($p15);
        $this->assertSame((float) $p10, (float) $p15, 'collective_unconscious should not keep mutating when below threshold.');
    }
}

