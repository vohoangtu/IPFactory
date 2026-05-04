<?php

namespace App\Modules\Simulation\Core\Contracts;

/**
 * WorldOS Data Graph (doc §15): sync Event and related nodes/relationships to graph DB.
 * When disabled or not configured, use NullWorldOsGraphService.
 */
interface WorldOsGraphServiceInterface
{
    /**
     * Sync a world event to the graph (create/merge Event node, link to Civilization/Person if in payload).
     *
     * @param array{id: string, type: string, universe_id: int, tick: int, payload?: array, actors?: array, location?: string} $eventData
     */
    public function syncEvent(array $eventData): void;
}
