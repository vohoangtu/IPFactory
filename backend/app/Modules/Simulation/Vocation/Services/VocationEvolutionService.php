<?php

namespace App\Modules\Simulation\Vocation\Services;

use App\Modules\Simulation\Vocation\Entities\VocationEntity;

class VocationEvolutionService
{
    /**
     * Resolve possible next vocations for an actor.
     */
    public function resolveNext(object $actor, VocationEntity $current, array $availableVocations): array
    {
        $eligible = [];
        $actorStats = $actor->stats ?? $actor->metrics ?? [];

        foreach ($availableVocations as $nextVocation) {
            // Check if it's on the path (simplified logic for V1)
            // If current vocation evolves to specific list, or match criteria
            if ($this->isEvolutionCandidate($actor, $current, $nextVocation, $actorStats)) {
                $eligible[] = $nextVocation;
            }
        }

        return $eligible;
    }

    protected function isEvolutionCandidate(object $actor, VocationEntity $current, VocationEntity $candidate, array $actorStats): bool
    {
        // 1. Tier check: must be higher or same level extension
        if ($candidate->tier <= $current->tier && $current->tier !== 0) {
            return false;
        }

        // 2. ID check: if current vocation has a fixed next path
        if ($current->evolvesTo !== null && $current->evolvesTo !== $candidate->id) {
            return false; 
        }

        // 3. Condition check (Dynamic evolution)
        return $candidate->isEligible($actorStats);
    }
}
