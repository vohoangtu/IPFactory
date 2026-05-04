<?php

namespace App\Contracts;

/**
 * doc §4, §12: causality graph update in event flow.
 * Records event chains (Event A → Event B → Event C) per universe for narrative/causal analysis.
 */
interface CausalityGraphServiceInterface
{
    /**
     * Record an event and its causal link to the previous event for the universe.
     */
    public function recordEvent(int $universeId, string $eventId, string $type, int $tick): void;

    /**
     * Record a specific relation between two entities/events.
     */
    public function recordRelation(string $src, string $relation, string $target, int $tick, array $metadata = []): void;

    /**
     * Retrieve the last N links recorded.
     */
    public function getRecentLinks(int $limit = 10): array;

    /**
     * Retrieve the last N links recorded for a specific universe.
     */
    public function getRecentLinksForUniverse(int $universeId, int $limit = 10): array;
}
