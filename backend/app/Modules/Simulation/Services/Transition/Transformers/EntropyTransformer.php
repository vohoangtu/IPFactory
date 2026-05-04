<?php

namespace App\Modules\Simulation\Services\Transition\Transformers;

use App\Modules\Simulation\Services\Transition\Contracts\StateTransformerInterface;
use App\Modules\Simulation\Core\Runtime\State\WorldState;

class EntropyTransformer implements StateTransformerInterface
{
    private float $crystallizationRatio;

    public function __construct() {
        $this->crystallizationRatio = config('power_system.crystallization_ratio', 0.8);
    }

    public function apply(WorldState $state, string $targetPowerSystem): WorldState
    {
        $oldEntropy = $state->getEntropy();
        
        // entropy_projected = entropy_old * (1 - crystallization_factor)
        $newEntropy = $oldEntropy * (1 - $this->crystallizationRatio);
        
        // consumed_entropy -> bootstrap_energy
        $bootstrapEnergy = $oldEntropy - $newEntropy;
        
        $state->setEntropy($newEntropy);
        $state->set('power_system_bootstrap_energy', ($state->get('power_system_bootstrap_energy', 0.0) + $bootstrapEnergy));
        
        return $state;
    }
}
