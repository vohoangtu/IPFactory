<?php

namespace App\Modules\Simulation\Actions;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Models\Chronicle;
use Illuminate\Support\Facades\Log;

/**
 * AutonomousAxiomMutationAction: Self-evolving physics (§V20).
 * Allows the multiverse to adapt its own rules based on stability.
 */
class AutonomousAxiomMutationAction
implements \App\Contracts\ActionInterface {
    /**
     * Attempt to mutate the world axioms based on universe performance.
     */
    public function execute(World $world): void
    {
        // Find the 'Most Stable' active universe
        $best = $world->universes()->where('status', 'active')->orderByDesc('structural_coherence')->first();
        
        if (!$best || $best->structural_coherence < 0.8) {
            return;
        }

        // Apply a small mutation towards the 'Best' state
        $axioms = $world->evolution_genome ?? [];
        
        // Phase 97: Chronos Sovereignty (§V21)
        // Hard-block time variables from automated evolution
        $forbidden = ['time_dilation', 'tick_rate', 'chronos_shift'];
        
        // Example: If entropy is low in the best universe, slightly bias the world towards low entropy
        $mutationOccurred = false;
        
        if (($best->entropy ?? 0.5) < 0.4) {
            $axioms['entropy_damping'] = ($axioms['entropy_damping'] ?? 0.0) + 0.01;
            $mutationOccurred = true;
        }

        if ($mutationOccurred) {
            $world->evolution_genome = $axioms;
            $world->save();
            
            Log::alert("EVOLUTION: World [{$world->id}] has autonomously mutated its Axioms based on the success of Universe #{$best->id}.");
            
            Chronicle::create([
                'universe_id' => $best->id,
                'from_tick' => $best->current_tick,
                'to_tick' => $best->current_tick,
                'type' => 'axiom_evolution',
                'raw_payload' => [
                'action' => 'legacy_event',
                'description' => "TIẾN HÓA VẬT LÝ: Bản quy luật của Thế giới đã tự thay đổi để tối ưu hóa sự ổn định. Quy luật mới: entropy_damping được tăng cường."
            ],
            ]);
        }
    }
}

