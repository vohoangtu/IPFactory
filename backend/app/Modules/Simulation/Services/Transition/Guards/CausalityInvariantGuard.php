<?php

namespace App\Modules\Simulation\Services\Transition\Guards;

use App\Modules\Simulation\Services\Transition\Contracts\InvariantGuardInterface;
use App\Modules\Simulation\Core\Runtime\State\WorldState;

/**
 * CausalityInvariantGuard - Σ causal_weights ≈ constant.
 * Prevents the causal graph from exploding or collapsing.
 */
class CausalityInvariantGuard implements InvariantGuardInterface
{
    public function verify(WorldState $originalState, WorldState $newState): void
    {
        $metadata = $newState->get('meta.causality', []);
        $intent = (float)($metadata['intent_weight'] ?? 0.1);
        $physics = (float)($metadata['physics_rigidity'] ?? 1.0);
        
        $totalWeight = $intent + $physics;
        
        // Σ causal_weights ≈ constant (Normalizing to 1.1 arbitrarily)
        if (abs($totalWeight - 1.1) > 0.05) {
            \Illuminate\Support\Facades\Log::warning("Causality Invariant Violated! Total: {$totalWeight}");
            
            // Correction: Re-normalize
            $scale = 1.1 / $totalWeight;
            $metadata['intent_weight'] = $intent * $scale;
            $metadata['physics_rigidity'] = $physics * $scale;
            
            $newState->set('meta.causality', $metadata);
        }
    }
}
