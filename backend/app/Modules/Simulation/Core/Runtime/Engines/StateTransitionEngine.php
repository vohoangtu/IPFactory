<?php

namespace App\Modules\Simulation\Core\Runtime\Engines;

use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\Causality\ImpactReport;
use App\Modules\Simulation\Core\Runtime\WorldKernel;
use Illuminate\Support\Facades\Log;

/**
 * Integrated State Transition Engine (ISTE) – The "World Brain" 🧠🌐
 * 
 * Phân tích các Gradient (độ dốc thay đổi) của các trường CFT và các báo cáo
 * nhân quả để phát hiện các bước nhảy lượng tử (Phase Bifurcations) của thế giới.
 */
class StateTransitionEngine
{
    /**
     * Analyze the global state and reports to determine if a transition is needed.
     * 
     * @param WorldState $state
     * @param ImpactReport[] $reports
     * @param int $tick
     */
    public function run(WorldState $state, array $reports, int $tick): void
    {
        $currentAttractor = $state->getActiveAttractor();
        $entropy = $state->getEntropy();
        $fields = $state->getFields();
        
        // 1. Detect Conflict-Myth Synergy (The Mythic Era Trigger)
        if ($currentAttractor !== 'mythic_era') {
            $conflictImpacts = $this->countImpactsByRule($reports, WorldKernel::RULE_CONFLICT);
            $mythImpacts = $this->countImpactsByRule($reports, WorldKernel::RULE_NARRATIVE);
            
            if ($conflictImpacts > 5 && $mythImpacts > 2 && $entropy > 0.8) {
                $this->transitionTo($state, 'mythic_era', "Chaos-Myth Synergy detected at tick $tick");
                return;
            }
        }

        // 2. Detect Resource-Knowledge Gradient (The Industrial Era Trigger)
        if ($currentAttractor !== 'industrial_dawn') {
            $knowledge = (float)($fields['knowledge'] ?? 0.0);
            $wealth = (float)($fields['wealth'] ?? 0.0);
            
            if ($knowledge > 0.7 && $wealth > 0.6) {
                $this->transitionTo($state, 'industrial_dawn', "Technological maturity reached at tick $tick");
                return;
            }
        }

        // 3. Detect Collapse (The Entropy Trigger)
        if ($entropy > 0.95 && $currentAttractor !== 'collapsed_reality') {
            $this->transitionTo($state, 'collapsed_reality', "Dimensional stability lost at tick $tick");
            return;
        }
    }

    protected function transitionTo(WorldState $state, string $newAttractor, string $reason): void
    {
        $oldAttractor = $state->getActiveAttractor();
        $state->setPreviousAttractor($oldAttractor);
        $state->setActiveAttractor($newAttractor);
        $state->setAttractorStability(0.1); // Stability drops during transition

        Log::alert("ISTE: Phase Bifurcation Detected!", [
            'from' => $oldAttractor,
            'to' => $newAttractor,
            'reason' => $reason
        ]);
        
        // Optional: Dispatch global event for history
    }

    protected function countImpactsByRule(array $reports, string $rule): int
    {
        $count = 0;
        foreach ($reports as $report) {
            if ($report->category === $rule) {
                $count++;
            }
        }
        return $count;
    }
}
