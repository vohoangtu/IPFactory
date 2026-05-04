<?php

namespace App\Modules\Simulation\Services\Transition\Transformers;

use App\Modules\Simulation\Services\Transition\Contracts\StateTransformerInterface;
use App\Modules\Simulation\Core\Runtime\State\WorldState;

/**
 * RealityStrainSimulator - Simulates the two-phase strain dynamics:
 * Shock Phase (Spike) -> Adaptation Phase (Decay).
 */
class RealityStrainSimulator implements StateTransformerInterface
{
    public function apply(WorldState $state, string $targetPowerSystem): WorldState
    {
        $currentStrain = (float)$state->get('reality_strain', 0.0);
        $transitionPhase = (int)$state->get('transition.phase', 0); // 0 = Shock, 1 = Adaptation
        
        if ($transitionPhase === 0) {
            // Shock Phase: Spike strain based on ruleset mismatch
            $spike = 0.45; 
            $newStrain = $currentStrain + $spike;
        } else {
            // Adaptation Phase: Decay strain
            $decay = 0.05;
            $newStrain = max(0.0, $currentStrain - $decay);
        }
        
        $state->set('reality_strain', $newStrain);
        
        return $state;
    }
}
