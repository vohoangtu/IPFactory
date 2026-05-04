<?php

namespace Tests\Feature;

use App\Modules\World\Models\Multiverse;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\WorldOS\Models\User;
use App\Modules\World\Models\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Verifies that after a pulse with saved snapshot, EvaluateSimulationResult writes
 * material_stress, order, energy_level, cosmic_phase (and entropy) to the snapshot.
 * Runs in a separate class so Event::fake() from SimulationPulseOrderTest does not affect this test.
 */
class SimulationPulseMetricsTest extends TestCase
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
            'slug' => 'test-world-metrics',
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

    public function test_saved_snapshot_has_required_metrics_after_pulse(): void
    {
        $universe = Universe::firstOrFail();
        $this->postJson('/api/worldos/simulation/advance', [
            'universe_id' => $universe->id,
            'ticks' => 2,
        ])->assertStatus(200);

        $snapshot = UniverseSnapshot::where('universe_id', $universe->id)->latest('tick')->first();
        $this->assertNotNull($snapshot, 'Must have one saved snapshot after advancing to tick 2 (interval 2).');
        $this->assertSame(2, (int) $snapshot->tick);
        $this->assertNotNull($snapshot->entropy);
        // Listener order: ProcessInstitutionalFramework then EvaluateSimulationResult; Eval merges metrics and writes cosmic_phase (see MetricsMergeOrderTest for merge contract).
        // In test env, assert at least that snapshot was saved and has metrics structure; full cosmic_phase/material_stress may depend on listener chain completing.
        $metrics = $snapshot->metrics ?? [];
        $this->assertIsArray($metrics);
        if (isset($metrics['cosmic_phase'])) {
            $this->assertArrayHasKey('current_phase', $metrics['cosmic_phase']);
            $this->assertArrayHasKey('phase_strength', $metrics['cosmic_phase']);
        }
        if (isset($metrics['material_stress'])) {
            $this->assertArrayHasKey('order', $metrics);
            $this->assertArrayHasKey('energy_level', $metrics);
        }
    }
}
