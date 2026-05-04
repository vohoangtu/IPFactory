<?php

namespace App\Modules\Simulation\Services\Transition\Transformers;

use App\Modules\Simulation\Services\Transition\Contracts\StateTransformerInterface;
use App\Modules\Simulation\Core\Runtime\State\WorldState;

class StabilityBinder implements StateTransformerInterface
{
    public function apply(WorldState $state, string $targetPowerSystem): WorldState
    {
        $oldStability = $state->getStabilityIndex();
        
        // stability_new = stability_old + binding_gain - adaptation_cost
        $bindingGain = 0.15; // Represents the coherence of the new laws
        $adaptationCost = 0.10; // Represents the initial friction of integration
        
        $newStability = $oldStability + $bindingGain - $adaptationCost;
        
        // Clamp to [0, 1] as per invariant rule
        $newStability = max(0.0, min(1.0, $newStability));
        
        $state->setStabilityIndex($newStability);
        
        return $state;
    }
}
