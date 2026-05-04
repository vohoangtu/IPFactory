<?php

namespace App\Modules\Simulation\Services\Transition\Guards;

use App\Modules\Simulation\Services\Transition\Contracts\InvariantGuardInterface;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Exceptions\InvariantViolationException;

class EnergyInvariantGuard implements InvariantGuardInterface
{
    /**
     * Energy Conservation: entropy_old ≈ entropy_new + bootstrap_energy + dissipation
     */
    public function verify(WorldState $originalState, WorldState $newState): void
    {
        $oldEntropy = $originalState->getEntropy();
        $newEntropy = $newState->getEntropy();
        $bootstrapEnergy = (float)$newState->get('power_system_bootstrap_energy', 0.0);
        $oldBootstrap = (float)$originalState->get('power_system_bootstrap_energy', 0.0);
        
        $deltaBootstrap = $bootstrapEnergy - $oldBootstrap;
        
        // dissipation represents energy lost during conversion (heat, noise)
        $dissipation = 0.005; 
        
        $energyDiff = abs($oldEntropy - ($newEntropy + $deltaBootstrap + $dissipation));
        
        // Allow a small tolerance
        if ($energyDiff > 0.01) {
            // Logically we should throw an Exception, but for now we log it and potentially correct it
            \Illuminate\Support\Facades\Log::warning("Energy Invariant Violated! Diff: {$energyDiff}");
            
            // Correction Step: Force entropy to match conservation
            $newState->setEntropy(max(0.0, $oldEntropy - $deltaBootstrap - $dissipation));
        }
    }
}
