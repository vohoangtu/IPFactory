<?php

namespace App\Modules\SocialGraph\Listeners;

use App\Modules\Simulation\Events\SimulationEventOccurred;
use App\Modules\Simulation\Core\Contracts\WorldOsGraphServiceInterface;

/**
 * Phase 5 Track B: sync WorldEvent to graph DB (Neo4j) when enabled.
 */
final class SyncWorldEventToGraph
{
    public function __construct(
        private readonly WorldOsGraphServiceInterface $graphService,
    ) {
    }

    public function handle(SimulationEventOccurred $event): void
    {
        $payload = $event->payload;
        if (! is_array($payload)) {
            return;
        }
        $eventData = [
            'id' => $payload['id'] ?? null,
            'type' => $payload['type'] ?? $event->type,
            'universe_id' => $payload['universe_id'] ?? $event->universeId,
            'tick' => $payload['tick'] ?? $event->tick,
            'payload' => $payload['payload'] ?? [],
            'actors' => $payload['actors'] ?? [],
            'location' => $payload['location'] ?? null,
        ];
        if ($eventData['id'] !== null) {
            $this->graphService->syncEvent($eventData);
        }
    }
}


