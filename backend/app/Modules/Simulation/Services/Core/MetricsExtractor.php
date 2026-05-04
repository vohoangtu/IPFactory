<?php

namespace App\Modules\Simulation\Services\Core;

use App\Modules\Simulation\Models\UniverseSnapshot;

class MetricsExtractor
{
    /**
     * Extract metrics from snapshot for evaluation (entropy trend, complexity, stability).
     */
    public function extract(UniverseSnapshot $snapshot): array
    {
        $state = $snapshot->state_vector ?? [];
        $entropy = (float) ($snapshot->entropy ?? 0);
        $stability = (float) ($snapshot->stability_index ?? 0);
        $zones = $state['zones'] ?? [];
        $zoneCount = is_array($zones) ? count($zones) : 0;
        $civilizations = $state['civilizations'] ?? [];
        $civilizationCount = is_array($civilizations) ? count($civilizations) : 0;

        return [
            'entropy' => $entropy,
            'stability_index' => $stability,
            'complexity' => $zoneCount,
            'civilization_count' => $civilizationCount,
            'entropy_trend' => 0, // would need history of snapshots
        ];
    }
}


