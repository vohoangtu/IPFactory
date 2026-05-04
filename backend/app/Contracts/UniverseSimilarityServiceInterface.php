<?php

namespace App\Contracts;

use App\Modules\Simulation\Models\UniverseSnapshot;

/**
 * doc §13: merge when similarity between two universes > threshold.
 * Returns merge candidate (sibling universe) when similarity >= threshold.
 */
interface UniverseSimilarityServiceInterface
{
    /**
     * Find neighboring universes with similarity above threshold.
     * 
     * @return array<array{universe_id: int, similarity: float}>
     */
    public function getNeighbors(UniverseSnapshot $snapshot, float $threshold = 0.5): array;

    /**
     * Find the best candidate for merging with the given snapshot.
     */
    public function getMergeCandidate(UniverseSnapshot $snapshot): ?array;
}

