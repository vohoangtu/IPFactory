<?php

namespace App\Modules\Narrative\Listeners;

use App\Modules\Simulation\Events\SimulationEventOccurred;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Narrative\Services\HistoricalFactEngine;
use App\Modules\Simulation\Core\Events\WorldEvent;
use Illuminate\Support\Facades\DB;

/**
 * Automagically records a HistoricalFact from a SimulationEventOccurred.
 * This bridges Layer 1 (Events) to Layer 2 (Facts) for Causality tracking.
 */
final class RecordHistoricalFact
{
    public function __construct(
        private readonly HistoricalFactEngine $factEngine
    ) {}

    public function handle(SimulationEventOccurred $event): void
    {
        // Avoid infinite loops if someone publishes from within fact recording
        // (HistoricalFactEngine is pure record-to-DB though)
        
        $payload = $event->payload;
        if (!isset($payload['id'], $payload['type'])) {
            return;
        }

        // We need a snapshot to populate metrics_after and tick.
        // Usually facts are recorded in the same tick.
        $snapshot = UniverseSnapshot::where('universe_id', $event->universeId)
            ->where('tick', $event->tick)
            ->first();

        if (!$snapshot) {
            // Virtual snapshot fallback or find latest
            $snapshot = new UniverseSnapshot([
                'universe_id' => $event->universeId,
                'tick' => $event->tick,
                'metrics' => $payload['metrics'] ?? [],
            ]);
        }

        // Reconstruct WorldEvent instance for the engine
        $we = new WorldEvent(
            id: $payload['id'],
            type: $payload['type'],
            universeId: (int)$event->universeId,
            tick: (int)$event->tick,
            location: $payload['location'] ?? null,
            actors: $payload['actors'] ?? [],
            impactScore: (float)($payload['impact_score'] ?? 0.0),
            causes: $payload['causes'] ?? [],
            payload: $payload['payload'] ?? $payload,
            parentId: $payload['parent_id'] ?? null
        );

        $this->factEngine->record($we, $snapshot);
    }
}



