<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Narrative\Models\HistoricalFact;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Events\WorldEvent;

/**
 * HistoricalFactEngine: record HistoricalFact from WorldEvent + Snapshot.
 */
class HistoricalFactEngine
{
    public function record(WorldEvent $event, UniverseSnapshot $snapshot): HistoricalFact
    {
        $metricsBefore = $snapshot->metrics ?? [];
        $metricsAfter = $metricsBefore;

        $payload = $event->payload;
        $category = $payload['category'] ?? ($payload['type'] ?? 'world_event');
        $year = $payload['year'] ?? null;
        $zoneId = $payload['zone_id'] ?? null;
        $civilizationId = $payload['civilization_id'] ?? null;
        $actors = $payload['actors'] ?? [];
        $institutions = $payload['institutions'] ?? [];
        $facts = $payload['facts'] ?? ($payload['events'] ?? [$payload['description'] ?? '']);

        return HistoricalFact::create([
            'world_event_id' => $event->id,
            'universe_id' => $event->universeId,
            'parent_id' => $event->parentId,
            'tick' => $event->tick,
            'year' => $year,
            'zone_id' => $zoneId,
            'civilization_id' => $civilizationId,
            'category' => (string) $category,
            'actors' => $actors,
            'institutions' => $institutions,
            'metrics_before' => $metricsBefore,
            'metrics_after' => $metricsAfter,
            'facts' => $facts,
        ]);
    }
}

