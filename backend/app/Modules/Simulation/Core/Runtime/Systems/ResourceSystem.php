<?php

namespace App\Modules\Simulation\Core\Runtime\Systems;

use App\Modules\Simulation\Core\Runtime\Contracts\WorldSystemInterface;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\World\Entities\ResourceEntity;
use App\Modules\Simulation\Core\Runtime\Causality\ImpactReport;
use App\Modules\Simulation\Core\Runtime\WorldKernel;

/**
 * 2️⃣ Resource Rule: Resources exist and are acquired or depleted.
 */
class ResourceSystem implements WorldSystemInterface
{
    public function update(array $context, int $tick): ?ImpactReport
    {
        $report = new ImpactReport('ResourceSystem', WorldKernel::PHASE_ENVIRONMENT, WorldKernel::RULE_EXTRACTION);
        $resources = $context['state']['resources'] ?? [];
        
        // Environment Pressure: Scarcity increases drift
        $globalScarcity = (float)($context['pressures']['resource_scarcity'] ?? 0.5);

        foreach ($resources as $resource) {
            // Natural regeneration vs decay
            $regen = 0.05 * (1.0 - $resource->scarcity);
            $loss = ($globalScarcity * 0.1);
            $resource->quantity = max(0, $resource->quantity + $regen - $loss);
            
            // Dynamic scarcity based on quantity
            $resource->scarcity = 1.0 - ($resource->quantity / 1000.0);
            $resource->scarcity = max(0.1, min(0.95, $resource->scarcity));

            if ($loss > 0.05) {
                $report->log('resource', $resource->id, 'depleted_by_scarcity', 'environment', 'scarcity', $loss, 1.0, [
                    'global_scarcity' => $globalScarcity
                ]);
            }
        }
        
        // Note: Entities are modified by reference in the context array

        return $report->hasImpacts() ? $report : null;
    }
}
