<?php

namespace Tests\Feature\Simulation;

use Tests\TestCase;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Supervisor\Handlers\RawGenerationPostSnapshotHandler;
use App\Modules\Narrative\Events\HistoricalEpochShifted;
use App\Modules\SocialGraph\Events\CelebrityEmerged;
use App\Modules\World\Events\ArtifactDiscovered;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RawGenerationPostSnapshotHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_handler_dispatches_events_when_raw_data_exists()
    {
        Event::fake();

        $universe = Universe::factory()->create();
        
        $stateVector = [
            'pending_history_events' => [
                [
                    'zone_id' => 1,
                    'event_type' => 'SingularityReached',
                    'impact_score' => 99.9,
                    'trigger_data' => ['cause' => 'max_entropy']
                ]
            ],
            'pending_celebrities' => [
                [
                    'id' => 101,
                    'zone_id' => 1,
                    'fame' => 0.85,
                    'vocation' => 'Scientist'
                ]
            ],
            'pending_artifacts' => [
                [
                    'id' => 201,
                    'zone_id' => 2,
                    'mass' => 1.5,
                    'knowledge_encoded' => 0.95
                ]
            ]
        ];

        $snapshot = UniverseSnapshot::factory()->create([
            'universe_id' => $universe->id,
            'tick' => 10,
            'state_vector' => $stateVector
        ]);

        $handler = new RawGenerationPostSnapshotHandler();
        $handler->handle($universe, $snapshot);

        Event::assertDispatched(HistoricalEpochShifted::class, function ($event) use ($universe) {
            return $event->universeId === $universe->id &&
                   $event->tick === 10 &&
                   $event->zoneId === 1 &&
                   $event->eventType === 'SingularityReached' &&
                   $event->impactScore === 99.9;
        });

        Event::assertDispatched(CelebrityEmerged::class, function ($event) use ($universe) {
            return $event->universeId === $universe->id &&
                   $event->tick === 10 &&
                   $event->zoneId === 1 &&
                   $event->agentId === 101 &&
                   $event->fame === 0.85 &&
                   $event->vocation === 'Scientist';
        });

        Event::assertDispatched(ArtifactDiscovered::class, function ($event) use ($universe) {
            return $event->universeId === $universe->id &&
                   $event->tick === 10 &&
                   $event->zoneId === 2 &&
                   $event->artifactId === 201 &&
                   $event->mass === 1.5 &&
                   $event->knowledgeEncoded === 0.95;
        });
    }

    public function test_handler_does_not_dispatch_events_if_empty()
    {
        Event::fake();

        $universe = Universe::factory()->create();
        
        $stateVector = [
            'pending_history_events' => [],
            'pending_celebrities' => [],
            'pending_artifacts' => []
        ];

        $snapshot = UniverseSnapshot::factory()->create([
            'universe_id' => $universe->id,
            'tick' => 10,
            'state_vector' => $stateVector
        ]);

        $handler = new RawGenerationPostSnapshotHandler();
        $handler->handle($universe, $snapshot);

        Event::assertNotDispatched(HistoricalEpochShifted::class);
        Event::assertNotDispatched(CelebrityEmerged::class);
        Event::assertNotDispatched(ArtifactDiscovered::class);
    }
}
