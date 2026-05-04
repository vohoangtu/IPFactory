<?php

namespace App\Modules\Simulation\Core\Runtime\Systems;

use App\Modules\Simulation\Core\Runtime\Contracts\WorldSystemInterface;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\Causality\ImpactReport;
use App\Modules\Simulation\Core\Runtime\WorldKernel;

/**
 * 3️⃣ Power Accumulation Rule: Actors/Institutions grow in power.
 */
class PowerSystem implements WorldSystemInterface
{
    public function update(array $context, int $tick): ?ImpactReport
    {
        $report = new ImpactReport('PowerSystem', WorldKernel::PHASE_SOCIAL, WorldKernel::RULE_COHESION);
        // Institutions leverage resources to grow power
        $institutions = $context['state']['institutions'] ?? [];
        $resources = $context['state']['resources'] ?? [];
        
        // Calculate wealth availability
        $totalWealth = collect($resources)
            ->where('type', 'gold')
            ->sum('quantity');

        foreach ($institutions as $inst) {
            if ($inst->isCollapsed()) continue;

            $growth = ($totalWealth * 0.001) + ($inst->legitimacy * 0.05);
            $inst->orgCapacity += $growth;
            
            // Influence expands
            foreach ($inst->influenceMap as $zone => $val) {
                $inst->influenceMap[$zone] = min(1.0, $val + 0.01 * $inst->orgCapacity / 100);
            }

            // V81 Semantic Reporting
            $report->log('institution', $inst->id, 'consolidated_power', 'world', 'power', $growth, 1.0, [
                'total_wealth' => $totalWealth,
                'legitimacy' => $inst->legitimacy
            ]);
        }

        return $report->hasImpacts() ? $report : null;
    }
}
