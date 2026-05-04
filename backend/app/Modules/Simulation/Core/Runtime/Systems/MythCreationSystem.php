<?php

namespace App\Modules\Simulation\Core\Runtime\Systems;

use App\Modules\Simulation\Core\Runtime\Contracts\WorldSystemInterface;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Intelligence\Entities\IdeaEntity;
use App\Modules\Simulation\Core\Runtime\Causality\ImpactReport;
use App\Modules\Simulation\Core\Runtime\WorldKernel;

/**
 * 7️⃣ Myth Creation Rule: Major events generate new narratives.
 */
class MythCreationSystem implements WorldSystemInterface
{
    public function update(array $context, int $tick): ?ImpactReport
    {
        $report = new ImpactReport('MythCreationSystem', WorldKernel::PHASE_META, WorldKernel::RULE_NARRATIVE);
        $entropy = $context['state']['entropy'] ?? 0.0;
        
        // High entropy/Chaos triggers myth generation
        if ($entropy > 0.9 && rand(0, 100) > 95) {
            $ideas = $context['state']['ideas'] ?? [];
            
            // --- Quantum Branching: Propose two conflicting narratives ---
            
            // Branch A: The Heroic Rescue (Low probability)
            $mythA = "The Advent of the Savior (Tick $tick)";
            $report->log('entropy', 'chaos', 'spawned', 'myth', $mythA, 1.0, 0.2, [
                'archetype' => 'savior',
                'branch' => 'heroic'
            ]);

            // Branch B: The Total Annihilation (High probability)
            $mythB = "The Mark of Despair (Tick $tick)";
            $report->log('entropy', 'chaos', 'spawned', 'myth', $mythB, 1.0, 0.8, [
                'archetype' => 'doom',
                'branch' => 'nihilistic'
            ]);

            // Note: We don't update $state here because DivergenceEngine will decide 
            // which one happens and we would update state in a post-process or within 
            // the system if we knew the outcome. For V81, we propose, Engine decides.
        }

        return $report->hasImpacts() ? $report : null;
    }
}
