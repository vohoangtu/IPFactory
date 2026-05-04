<?php

namespace App\Modules\Simulation\Services\Culture;

use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

/**
 * ResonanceAuditorService: Replaces the manual 'Observation Interference' (§V20).
 * The multiverse audits itself to maintain stability autonomously.
 */
class ResonanceAuditorService
{
    /**
     * Audit a universe and apply Resonance Bonus based on its own coherence.
     */
    public function audit(Universe $universe): void
    {
        // Deprecated
    }

    public function runWithState(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, int $currentTick): void
    {
        $coherence = (float) $state->get('structural_coherence', 0.5);
        
        // High coherence creates a "Positive Feedback Loop" (Resonance)
        $resonance = ($coherence > 0.7) ? ($coherence - 0.7) * 0.5 : 0.0;
        
        // Prevent total decay if the universe is healthy
        $baseResonance = ($coherence > 0.5) ? 0.02 : 0.0;

        $totalBonus = round($resonance + $baseResonance, 4);

        $state->set('meta.resonance', $totalBonus);
        
        if ($totalBonus > 0.05) {
            Log::info("RESONANCE: Universe {$state->get('universe_id')} has achieved self-stabilization (+{$totalBonus} SCI) at tick {$currentTick}");
        }
    }
}



