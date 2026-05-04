<?php

namespace Tests\Feature\Api;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\Intelligence\Models\ActorEvent;
use App\Modules\Intelligence\Models\AgentDecision;
use App\Modules\World\Models\Multiverse;
use App\Modules\Narrative\Models\MythScar;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\WorldOS\Models\User;
use App\Modules\World\Models\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorldosObserverContractTest extends TestCase
{
    use RefreshDatabase;

    protected Universe $parentUniverse;

    protected Universe $childUniverse;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create(), ['*']);

        $multiverse = Multiverse::create([
            'name' => 'Observer Test',
            'slug' => 'observer-test',
            'config' => [],
        ]);

        $world = World::create([
            'multiverse_id' => $multiverse->id,
            'name' => 'Observer World',
            'slug' => 'observer-world',
            'axiom' => ['order' => 0.8],
            'world_seed' => [],
            'origin' => 'generic',
            'global_tick' => 0,
        ]);

        $this->parentUniverse = Universe::create([
            'world_id' => $world->id,
            'multiverse_id' => $multiverse->id,
            'name' => 'Primary Branch',
            'current_tick' => 12,
            'status' => 'active',
            'epoch' => 'Bronze Dawn',
            'entropy' => 0.22,
            'structural_coherence' => 0.88,
            'state_vector' => [
                'metrics' => [
                    'population' => 1200,
                    'innovation' => 0.44,
                ],
            ],
            'axioms' => [
                'order' => 0.8,
                'curiosity' => 0.6,
            ],
        ]);

        $this->childUniverse = Universe::create([
            'world_id' => $world->id,
            'multiverse_id' => $multiverse->id,
            'parent_universe_id' => $this->parentUniverse->id,
            'name' => 'Counterfactual Branch',
            'current_tick' => 15,
            'forked_at_tick' => 8,
            'status' => 'forked',
            'entropy' => 0.3,
            'structural_coherence' => 0.72,
            'state_vector' => [
                'metrics' => [
                    'population' => 950,
                    'innovation' => 0.61,
                ],
            ],
        ]);

        UniverseSnapshot::create([
            'universe_id' => $this->parentUniverse->id,
            'tick' => 12,
            'state_vector' => ['metrics' => ['population' => 1200, 'innovation' => 0.44]],
            'entropy' => 0.22,
            'stability_index' => 0.88,
            'metrics' => ['population' => 1200, 'innovation' => 0.44],
        ]);

        UniverseSnapshot::create([
            'universe_id' => $this->childUniverse->id,
            'tick' => 15,
            'state_vector' => ['metrics' => ['population' => 950, 'innovation' => 0.61]],
            'entropy' => 0.3,
            'stability_index' => 0.72,
            'metrics' => ['population' => 950, 'innovation' => 0.61],
        ]);

        MythScar::create([
            'universe_id' => $this->parentUniverse->id,
            'name' => 'Broken Rite',
            'description' => 'An unresolved mythic disturbance.',
            'severity' => 0.8,
            'created_at_tick' => 7,
        ]);
    }

    public function test_universe_detail_and_metrics_are_returned_with_normalized_shape(): void
    {
        $detailResponse = $this->getJson("/api/worldos/universes/{$this->parentUniverse->id}");

        $detailResponse->assertOk();
        $detailResponse->assertJsonPath('data.id', $this->parentUniverse->id);
        $detailResponse->assertJsonPath('data.status', 'active');
        $detailResponse->assertJsonPath('data.current_tick', 12);
        $detailResponse->assertJsonPath('data.era', 'Bronze Dawn');
        $detailResponse->assertJsonPath('data.branch_count', 1);
        $detailResponse->assertJsonPath('data.anomaly_count', 1);
        $detailResponse->assertJsonPath('data.latest_snapshot.tick', 12);

        $metricsResponse = $this->getJson("/api/worldos/universes/{$this->parentUniverse->id}/metrics");

        $metricsResponse->assertOk();
        $metricsResponse->assertJsonPath('data.universe_id', $this->parentUniverse->id);
        $metricsResponse->assertJsonPath('data.snapshot_count', 1);
        $metricsResponse->assertJsonPath('data.branch_count', 1);
        $metricsResponse->assertJsonPath('data.anomaly_count', 1);
    }

    public function test_snapshot_create_and_snapshot_list_follow_observer_contract(): void
    {
        UniverseSnapshot::where('universe_id', $this->parentUniverse->id)->delete();

        $createResponse = $this->postJson("/api/worldos/universes/{$this->parentUniverse->id}/snapshots");

        $createResponse->assertOk();
        $createResponse->assertJsonPath('ok', true);
        $createResponse->assertJsonPath('data.snapshot.tick', 12);
        $createResponse->assertJsonPath('data.snapshot.label', 'Snapshot 12');
        $createResponse->assertJsonPath('data.snapshot.note', 'Tick 12: Entropy=0.22, Stability=0.88');

        $listResponse = $this->getJson("/api/worldos/universes/{$this->parentUniverse->id}/snapshots");

        $listResponse->assertOk();
        $listResponse->assertJsonPath('data.0.tick', 12);
        $listResponse->assertJsonPath('data.0.label', 'Snapshot 12');
        $listResponse->assertJsonStructure([
            'data' => [[
                'id',
                'universe_id',
                'tick',
                'label',
                'created_at',
                'note',
                'entropy',
                'stability_index',
                'metrics',
            ]],
        ]);
    }

    public function test_forks_collection_and_compare_endpoint_return_expected_shape(): void
    {
        $forksResponse = $this->getJson("/api/worldos/universes/{$this->parentUniverse->id}/forks");

        $forksResponse->assertOk();
        $forksResponse->assertJsonPath('data.0.id', $this->childUniverse->id);
        $forksResponse->assertJsonPath('data.0.divergence_tick', 8);
        $forksResponse->assertJsonPath('data.0.status', 'volatile');

        $compareResponse = $this->getJson("/api/worldos/universes/{$this->parentUniverse->id}/forks/compare?branch_id={$this->childUniverse->id}");

        $compareResponse->assertOk();
        $compareResponse->assertJsonPath('data.universe_id', $this->parentUniverse->id);
        $compareResponse->assertJsonPath('data.branch_id', $this->childUniverse->id);
        $compareResponse->assertJsonPath('data.source.tick', 12);
        $compareResponse->assertJsonPath('data.branch.tick', 15);
        $compareResponse->assertJsonPath('data.metric_deltas.population', -250.0);
    }

    public function test_actor_endpoints_return_normalized_observer_payloads(): void
    {
        $actor = Actor::create([
            'universe_id' => $this->parentUniverse->id,
            'name' => 'Archivist Sol',
            'archetype' => 'Historian',
            'traits' => ['curiosity' => 0.92, 'caution' => 0.44],
            'biography' => 'Keeps the memory lattice intact.',
            'is_alive' => true,
            'birth_tick' => 2,
            'life_stage' => 'Ascendant',
            'metrics' => ['influence' => 8.4],
            'stats' => ['influence' => 8.4],
            'capabilities' => ['memory' => 0.9],
            'vitality' => ['stamina' => 0.7],
        ]);

        ActorEvent::create([
            'actor_id' => $actor->id,
            'tick' => 11,
            'event_type' => 'archive_revision',
            'context' => ['summary' => 'Reframed the official chronicle.'],
        ]);

        AgentDecision::create([
            'actor_id' => $actor->id,
            'universe_id' => $this->parentUniverse->id,
            'tick' => 12,
            'action_type' => 'preserve_memory',
            'utility_score' => 0.81,
            'impact' => ['memory' => 0.2],
            'reasoning' => 'The branch must retain a stable recall path.',
            'confidence' => 0.91,
            'traits_snapshot' => ['curiosity' => 0.92],
            'context_snapshot' => ['pressure' => 'high'],
        ]);

        $actorsResponse = $this->getJson("/api/worldos/universes/{$this->parentUniverse->id}/actors");
        $actorsResponse->assertOk();
        $actorsResponse->assertJsonPath('data.0.name', 'Archivist Sol');
        $actorsResponse->assertJsonPath('data.0.role', 'Historian');
        $actorsResponse->assertJsonPath('data.0.last_decision', 'The branch must retain a stable recall path.');

        $detailResponse = $this->getJson("/api/worldos/actors/{$actor->id}");
        $detailResponse->assertOk();
        $detailResponse->assertJsonPath('data.name', 'Archivist Sol');
        $detailResponse->assertJsonPath('data.recent_events.0.summary', 'Reframed the official chronicle.');

        $decisionsResponse = $this->getJson("/api/worldos/actors/{$actor->id}/decisions");
        $decisionsResponse->assertOk();
        $decisionsResponse->assertJsonPath('data.0.action_type', 'preserve_memory');
        $decisionsResponse->assertJsonPath('data.0.utility_score', 0.81);
    }
}
