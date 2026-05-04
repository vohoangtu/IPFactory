<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Contracts\ActorRepositoryInterface;

/**
 * Phase 42.3: Information Layer Service.
 * Manages "Knowledge Bandwidth" and "Information Entropy".
 */
class InformationLayerService
{
    public function __construct(
        private ActorRepositoryInterface $actorRepository
    ) {}

    /**
     * Calculates the Information Entropy of the universe based on culture group distribution.
     * High entropy = High fragmentation = Low knowledge bandwidth.
     */
    public function calculateInformationEntropy(Universe $universe): float
    {
        $actors = $this->actorRepository->findByUniverse($universe->id);
        $alive = array_filter($actors, fn($a) => $a->isAlive);
        $total = count($alive);
        
        if ($total === 0) return 0.0;

        $groups = [];
        foreach ($alive as $actor) {
            $groupId = $actor->metrics['culture_group'] ?? 'default';
            $groups[$groupId] = ($groups[$groupId] ?? 0) + 1;
        }

        $entropy = 0;
        foreach ($groups as $count) {
            $p = $count / $total;
            $entropy -= $p * log($p, 2);
        }

        // Normalize entropy (max entropy for N groups is log2(N))
        // We cap it for simulation utility
        return min(3.0, $entropy) / 3.0; // 0.0 to 1.0
    }

    /**
     * returns a multiplier for Knowledge Field growth based on entropy.
     */
    public function getKnowledgeBandwidth(Universe $universe): float
    {
        $entropy = $this->calculateInformationEntropy($universe);
        
        // Bandwidth = 1.0 (perfect) to 0.5 (heavy fragmentation)
        return max(0.5, 1.0 - ($entropy * 0.5));
    }
}

