<?php

namespace App\Modules\World\Services;

use App\Modules\Simulation\Models\MaterialInstance;
use App\Modules\Simulation\Core\Runtime\State\WorldState;

/**
 * PressureResolver: Resolve material pressure/stress across dimensions.
 * Aligned with WorldOS V6 §8.3 (Resonance & Stress).
 */
class PressureResolver
{
    /**
     * Resolve total material stress for a zone or world state.
     */
    public function resolve(array $zone, WorldState $state): float
    {
        $zoneId = $zone['id'] ?? 0;
        
        // 1. Fetch active materials in this zone
        $instances = MaterialInstance::where('universe_id', $state->getUniverseId())
            ->where('lifecycle', 'active')
            ->where('context->zone_id', $zoneId)
            ->with('material')
            ->get();

        if ($instances->isEmpty()) {
            return 0.0;
        }

        // 2. Calculate Resonance (>=2 materials same slug -> 1.5x effect)
        $slugCounts = $instances->groupBy(fn($i) => $i->material->slug)->map->count();
        
        $totalStress = 0.0;
        foreach ($instances as $instance) {
            $slug = $instance->material->slug;
            $resMult = ($slugCounts[$slug] >= 2) ? 1.5 : 1.0;
            
            $coefs = $instance->material->pressure_coefficients ?? [];
            $output = (float)($instance->context['output'] ?? 1.0);
            
            // Stress formula: entropy_coef * output * resonance
            $totalStress += ($coefs['entropy'] ?? 0.0) * $output * $resMult * 0.02;
        }

        return min(1.0, max(0.0, $totalStress));
    }
}
