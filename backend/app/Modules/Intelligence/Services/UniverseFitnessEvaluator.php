<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;

/**
 * Phase 31: Universe Fitness Evaluator.
 * Formulas based on Spec V23 for Darwinian Multiverse logic.
 * F = (Order * 0.3) + (Knowledge * 0.5) + (Stability * 0.2)
 */
class UniverseFitnessEvaluator
{
    public function evaluate(Universe $universe): float
    {
        $entropy = (float)($universe->entropy ?? 0.5);
        $stability = (float)($universe->structural_coherence ?? 0.5);
        
        $stateVector = $universe->state_vector ?? [];
        $fields = $stateVector['fields'] ?? [];
        $knowledge = (float)($fields['knowledge'] ?? 0.0);

        // Order is the inverse of entropy (clamped to 0-1 range for scoring)
        // Entropy 0.0 -> Order 1.0, Entropy 2.0 -> Order 0.0
        $order = 1.0 - ($entropy / 2.0);
        $order = max(0.0, min(1.0, $order));

        $fitness = ($order * 0.3) + ($knowledge * 0.5) + ($stability * 0.2);

        return round($fitness, 4);
    }
}

