<?php

namespace App\Modules\Simulation\Listeners;

use App\Contracts\CausalityGraphServiceInterface;
use App\Modules\Simulation\Events\SimulationEventOccurred;

/**
 * doc §4: after event is published, update causality graph (Event A → Event B → Event C).
 */
final class SyncWorldEventToCausalityGraph
{
    public function __construct(
        private readonly CausalityGraphServiceInterface $causalityGraph
    ) {
    }

    public function handle(SimulationEventOccurred $event): void
    {
        $payload = $event->payload;
        if (! is_array($payload)) {
            return;
        }
        $eventId = $payload['id'] ?? null;
        if ($eventId === null || $eventId === '') {
            return;
        }
        $this->causalityGraph->recordEvent(
            $event->universeId,
            (string) $eventId,
            $event->type,
            $event->tick
        );
    }
}

