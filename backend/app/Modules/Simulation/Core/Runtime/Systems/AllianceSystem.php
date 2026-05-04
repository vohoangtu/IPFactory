<?php

namespace App\Modules\Simulation\Core\Runtime\Systems;

use App\Modules\Simulation\Core\Runtime\Contracts\WorldSystemInterface;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\Causality\ImpactReport;
use App\Modules\Simulation\Core\Runtime\WorldKernel;

/**
 * 4️⃣ Alliance Rule: Actors cooperate for mutual benefit.
 */
class AllianceSystem implements WorldSystemInterface
{
    public function update(array $context, int $tick): ?ImpactReport
    {
        $report = new ImpactReport('AllianceSystem', WorldKernel::PHASE_SOCIAL, WorldKernel::RULE_COHESION);
        $actors = $context['state']['actors'] ?? [];
        
        // Simple heuristic: High empathy actors near each other form trust
        foreach ($actors as $actor) {
            if (!$actor->isAlive) continue;
            
            $empathy = (float)($actor->traits[4] ?? 0.5); // Empathy trait
            if ($empathy > 0.7) {
                $actor->incrementInfluence(0.01);
                // Potential for group formation logic here
            }
        }

        return $report->hasImpacts() ? $report : null;
    }
}
