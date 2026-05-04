<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

/**
 * Phase 37: Causal Inertia & Smooth Transition.
 * Gradually nudges the current kernel_genome towards the target_genome using LERP.
 */
class GenomeTransitionService
{
    protected float $inertiaFactor = 0.05; // 5% per tick

    /**
     * Perform one step of genome transition.
     */
    public function step(Universe $universe): void
    {
        $stateVector = $universe->state_vector ?? [];
        $targetGenome = $stateVector['target_genome'] ?? null;
        $currentGenome = $universe->kernel_genome ?? [];

        if (!$targetGenome) {
            return;
        }

        $modified = false;
        $newGenome = $currentGenome;

        foreach ($targetGenome as $gene => $targetValue) {
            $currentValue = $currentGenome[$gene] ?? $targetValue;
            
            if (abs($currentValue - $targetValue) > 0.0001) {
                // LERP: Current + (Target - Current) * Inertia
                $delta = ($targetValue - $currentValue) * $this->inertiaFactor;
                $newGenome[$gene] = round($currentValue + $delta, 5);
                $modified = true;
            }
        }

        if ($modified) {
            $universe->kernel_genome = $newGenome;
            // No save here to avoid redundant DB writes; MetaCosmicStage will save it.
        }
    }
}

