<?php

namespace App\Modules\Simulation\Services\Core;

use App\Contracts\UniverseSimilarityServiceInterface;
use App\Modules\Simulation\Models\UniverseSnapshot;

/**
 * Stub: never suggests merge. Implement real logic (e.g. compare state_vector with siblings) for merge support.
 */
final class NullUniverseSimilarityService implements UniverseSimilarityServiceInterface
{
    public function getMergeCandidate(UniverseSnapshot $snapshot): ?array
    {
        return null;
    }

    public function getNeighbors(UniverseSnapshot $snapshot, float $threshold = 0.5): array
    {
        return [];
    }
}


