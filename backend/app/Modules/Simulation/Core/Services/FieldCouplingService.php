<?php

namespace App\Modules\Simulation\Core\Services;

use App\Modules\Simulation\Core\Runtime\State\WorldState;

/**
 * FieldCouplingService – Manages dynamic, non-linear influence between civilization fields.
 * 
 * Instead of static weights, influence is a function of:
 *   - Global belief/openness
 *   - Collective memory/trauma
 *   - Active narratives
 */
class FieldCouplingService
{
    public function getCouplingWeight(string $fromField, string $toField, WorldState $state): float
    {
        $baseWeights = $this->getBaseMatrix();
        $weight = $baseWeights[$fromField][$toField] ?? 0.0;

        // Contextual override: Knowledge <-> Belief relationship
        if ($fromField === 'knowledge' && $toField === 'belief') {
            $beliefValue = (float) $state->get('fields.belief', 0.5);
            // High belief creates skepticism towards knowledge (user's suggestion)
            if ($beliefValue > 0.6) {
                return -0.6;
            }
            return -0.2;
        }

        // Contextual override: Fear <-> Order
        if ($fromField === 'fear' && $toField === 'order') {
            $authority = (float) $state->get('fields.authority', 0.5);
            // If authority is high, fear leads to order. If low, fear leads to chaos.
            return ($authority > 0.6) ? 0.4 : -0.5;
        }

        return $weight;
    }

    public function getVolatility(WorldState $state): float
    {
        $entropy = (float) $state->getEntropy();
        // High entropy increases system volatility/unpredictability
        return 0.01 + ($entropy * 0.05);
    }

    protected function getBaseMatrix(): array
    {
        return [
            'survival'  => ['power' => 0.1, 'wealth' => 0.1, 'order' => 0.1, 'entropy' => -0.1],
            'power'     => ['wealth' => 0.2, 'knowledge' => -0.2, 'authority' => 0.3, 'fear' => 0.1],
            'wealth'    => ['survival' => 0.1, 'power' => 0.2, 'knowledge' => 0.2, 'authority' => 0.1],
            'knowledge' => ['power' => -0.2, 'meaning' => 0.2, 'resonance' => 0.2, 'belief' => -0.2],
            'meaning'   => ['survival' => 0.1, 'authority' => 0.2, 'order' => 0.2, 'resonance' => 0.4],
            'authority' => ['power' => 0.3, 'order' => 0.4, 'fear' => 0.2],
            'fear'      => ['survival' => -0.2, 'authority' => 0.2, 'entropy' => 0.3],
            'order'     => ['survival' => 0.2, 'authority' => 0.4, 'entropy' => -0.3],
            'entropy'   => ['order' => -0.4, 'fear' => 0.3, 'resonance' => -0.3],
            'resonance' => ['meaning' => 0.4, 'order' => 0.2, 'entropy' => -0.2],
        ];
    }
}
