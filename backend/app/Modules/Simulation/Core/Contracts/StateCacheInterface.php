<?php

namespace App\Modules\Simulation\Core\Contracts;

/**
 * Optional hot state cache for universe state_vector (Phase 2 §2.3).
 * When driver=redis, StateSynchronizer writes after sync; EngineDriver can prefer cache when reading for advance.
 */
interface StateCacheInterface
{
    /**
     * Get cached state for universe, or null if missing/expired.
     *
     * @return array{state_vector: array, tick: int}|null
     */
    public function get(int $universeId): ?array;

    /**
     * Store state in cache (e.g. Redis with TTL).
     */
    public function set(int $universeId, array $stateVector, int $tick): void;
}
