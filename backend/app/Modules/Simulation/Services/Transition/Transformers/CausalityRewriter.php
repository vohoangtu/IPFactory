<?php

namespace App\Modules\Simulation\Services\Transition\Transformers;

use App\Modules\Simulation\Services\Transition\Contracts\StateTransformerInterface;
use App\Modules\Simulation\Core\Runtime\State\WorldState;

/**
 * CausalityRewriter - Re-weights the causal graph weights.
 * SCI increases because physical law rigidity decreases and intent influence increases.
 */
class CausalityRewriter implements StateTransformerInterface
{
    public function apply(WorldState $state, string $targetPowerSystem): WorldState
    {
        $oldSci = (float)$state->get('sci', 0.2);
        
        // SCI = weight(actor_intent) / weight(physical_law)
        // Adjusting weights based on power system type
        $weightShift = match($targetPowerSystem) {
            'martial_arts' => 0.15,
            'magic', 'cultivation' => 0.25,
            'psionic' => 0.35,
            default => 0.05
        };
        
        $newSci = $oldSci + $weightShift;
        
        $state->set('sci', min(1.0, $newSci));
        
        // Optional: Update causal_graph_metadata
        $metadata = $state->get('meta.causality', []);
        $metadata['intent_weight'] = ($metadata['intent_weight'] ?? 0.1) + $weightShift;
        $metadata['physics_rigidity'] = max(0.1, ($metadata['physics_rigidity'] ?? 1.0) - ($weightShift / 2));
        $state->set('meta.causality', $metadata);
        
        return $state;
    }
}
