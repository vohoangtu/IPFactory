<?php

namespace App\Modules\Intelligence\Services;

class ReplicatorDistributionUpdater
{
    /**
     * Compute archetype ratios for the population to drive Replicator Dynamics penalty.
     */
    public function computeRatios(array $actors): array
    {
        $counts = [];
        $total = count($actors);
        
        if ($total === 0) {
            return [];
        }

        foreach ($actors as $actor) {
            $counts[$actor->archetype] = ($counts[$actor->archetype] ?? 0) + 1;
        }

        $ratios = [];
        foreach ($counts as $archetype => $count) {
            $ratios[$archetype] = $count / $total;
        }

        return $ratios;
    }
}
