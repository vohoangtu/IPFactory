<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Intelligence\Models\ActorEvent;
use App\Modules\World\Models\WorldEvent;
use Illuminate\Support\Collection;

/**
 * NarrativeEventRegistry: Centralized repository for narrative-worthy simulation events.
 * Bridges the gap between raw causal links and high-level narrative tokens.
 */
class NarrativeEventRegistry
{
    /**
     * Get a consolidated list of events for a universe and tick range.
     */
    public function getEventsForContext(int $universeId, int $startTick, int $endTick): Collection
    {
        // 1. Fetch Actor Events (Micro-level)
        $actorEvents = ActorEvent::whereIn('actor_id', function($query) use ($universeId) {
            $query->select('id')->from('actors')->where('universe_id', $universeId);
        })
        ->whereBetween('tick', [$startTick, $endTick])
        ->get()
        ->map(function ($event) {
            return [
                'type' => 'ACTOR_EVENT',
                'tick' => $event->tick,
                'event_type' => $event->event_type,
                'summary' => $this->formatActorEventSummary($event),
                'importance' => $this->calculateImportance($event)
            ];
        });

        // 2. Fetch Chronicle Events (Macro-level)
        $worldEvents = \App\Modules\Narrative\Models\Chronicle::where('universe_id', $universeId)
            ->whereBetween('from_tick', [$startTick, $endTick])
            ->get()
            ->map(function ($event) {
                return [
                    'type' => 'CHRONICLE_EVENT',
                    'tick' => $event->from_tick,
                    'event_type' => $event->type,
                    'summary' => $event->content,
                    'importance' => $event->importance ?? 0.8
                ];
            });

        return $actorEvents->concat($worldEvents)->sortBy('tick');
    }

    protected function formatActorEventSummary(ActorEvent $event): string
    {
        $context = $event->context;
        return match ($event->event_type) {
            'PROMOTED' => "Actor #{$event->actor_id} was promoted to {$context['new_archetype']}.",
            'CONFLICT' => "Actor #{$event->actor_id} was involved in a conflict (Severity: {$context['severity']}).",
            'MIGRATION' => "Actor #{$event->actor_id} migrated to zone {$context['target_zone']}.",
            default => "Actor #{$event->actor_id} triggered {$event->event_type}."
        };
    }

    protected function calculateImportance(ActorEvent $event): float
    {
        // Placeholder for more complex importance logic based on weights
        return match ($event->event_type) {
            'PROMOTED', 'DEATH' => 0.9,
            'CONFLICT' => 0.6,
            'MIGRATION' => 0.3,
            default => 0.1
        };
    }
}

