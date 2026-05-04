<?php

namespace App\Modules\Simulation\Core\Runtime\Systems;

use App\Modules\Simulation\Core\Runtime\Contracts\WorldSystemInterface;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\Causality\ImpactReport;
use App\Modules\Simulation\Core\Runtime\WorldKernel;

/**
 * 5️⃣ Conflict Rule: Clash of interests leads to war/instability.
 */
class ConflictSystem implements WorldSystemInterface
{
    public function update(array $context, int $tick): ?ImpactReport
    {
        $report = new ImpactReport('ConflictSystem', WorldKernel::PHASE_META, WorldKernel::RULE_CONFLICT);
        $state = $context['state'];
        $resources = $state['resources'] ?? [];
        
        // 1. Calculate resource stress per zone
        $zones = $state['zones'] ?? [];
        $resources = $context['state']['resources'] ?? [];
        $resourceMap = [];
        foreach ($resources as $res) {
            $resourceMap[$res->zone_id] = $res;
        }

        $globalStress = 0.0;
        foreach ($zones as $zone) {
            $zoneId = $zone['id'];
            $res = $resourceMap[$zoneId] ?? null;
            
            if ($res) {
                // Stress = population / capacity (approximation: scarcity + high hunger)
                $stress = (float)($res->scarcity ?? 0.5);
                if ($res->quantity <= 0.1) $stress += 0.3; // Desperation bonus
                
                $globalStress += $stress;
            }
        }

        $avgStress = count($zones) > 0 ? ($globalStress / count($zones)) : 0.0;

        // 2. High stress triggers social instability & pressure
        if ($avgStress > 0.6) {
            $instabilityDelta = ($avgStress - 0.6) * 0.15;
            
            $report->log('system', 'resource_stress', 'triggered_social_pressure', 'world', 'stability', -$instabilityDelta, 1.0, [
                'avg_stress' => $avgStress,
                'tick' => $tick,
                'mutation' => [
                    'entropy' => ($state['entropy'] ?? 0.0) + ($instabilityDelta * 0.5),
                    'stability_index' => max(0, ($state['stability_index'] ?? 1.0) - $instabilityDelta)
                ]
            ]);

            // V9: Impact the Social Layer pressures directly
            $pressures = $state['pressures'] ?? [];
            $pressures['economic'] = min(1.0, ($pressures['economic'] ?? 0.0) + $instabilityDelta);
            $pressures['war'] = min(1.0, ($pressures['war'] ?? 0.0) + $instabilityDelta * 0.5);
            $context['state']['pressures'] = $pressures;
        }

        return $report->hasImpacts() ? $report : null;
    }
}
