<?php

namespace App\Modules\Intelligence\Services\Dashboard;

use App\Modules\Simulation\Models\CivilizationAttractor;

class RiskMetricsService
{
    public function __construct(
        private StateMetricsService $stateService
    ) {}

    /**
     * Get future predictions and existential risks for the dashboard.
     */
    public function getRiskMonitor(): array
    {
        $macro = $this->stateService->getMacroState();
        $tick = $macro['tick'] ?? 0;
        
        $activeAlerts = [];
        $collapseRisk = 0.1;
        $entropyRisk = 0.1;

        // Use basic heuristics from macro instead of missing causal_trajectories table
        $stability = $macro['stability'] ?? 0.5;
        $entropy = $macro['entropy'] ?? 0.2;
        $knowledge = $macro['tech'] ?? 0.5;
        // When knowledge is 0 (no data or early sim), avoid showing 100% stagnation — treat as neutral
        $knowledgeForStagnation = $knowledge > 0 ? $knowledge : 0.5;

        // Dynamic risks based on current state
        if ($stability < 0.3) {
            $collapseRisk = max(0.4, 1.0 - $stability);
            if ($stability < 0.15) {
                $activeAlerts[] = "Collapse Trajectory Detected: " . round($collapseRisk * 100) . "%";
            }
        }
        
        if ($entropy > 0.6) {
            $entropyRisk = max(0.4, $entropy);
            if ($entropy > 0.8) {
                $activeAlerts[] = "Entropy Cascade Imminent: " . round($entropyRisk * 100) . "%";
            }
        }

        if (($macro['stability'] ?? 0.5) < 0.2) {
            $collapseRisk = max($collapseRisk, 0.85);
            $activeAlerts[] = "System Critical: Extreme Instability";
        }
        if (($macro['entropy'] ?? 0.5) > 0.85) {
            $entropyRisk = max($entropyRisk, 0.90);
            $activeAlerts[] = "System Critical: Chaotic Entropy Limits Reached";
        }
        
        if ($knowledge < 0.3 && $tick > 100) {
             $activeAlerts[] = "Stagnation Horizon Ahead";
        }

        return [
            'indicators' => [
                ['name' => 'Collapse Probability', 'value' => $collapseRisk],
                ['name' => 'Heat Death Risk', 'value' => $entropyRisk],
                ['name' => 'Innovation Stagnation', 'value' => max(0, min(1.0, 1.0 - ($knowledgeForStagnation * 2)))],
            ],
            'active_alerts' => array_unique($activeAlerts),
        ];
    }
}

