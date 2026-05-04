<?php

namespace App\Modules\Simulation\Services\Core;

use App\Contracts\CausalityGraphServiceInterface;

/**
 * No-op implementation. Use RedisCausalityGraphService for actual causality chain storage.
 */
final class NullCausalityGraphService implements CausalityGraphServiceInterface
{
    public function recordEvent(int $universeId, string $eventId, string $type, int $tick): void
    {
    }

    public function recordRelation(string $src, string $tgt, string $rel, int $tick, array $meta = []): void
    {
    }

    public function getRecentLinks(int $limit = 50): array
    {
        return [];
    }

    public function getRecentLinksForUniverse(int $universeId, int $limit = 10): array
    {
        return [];
    }
}

