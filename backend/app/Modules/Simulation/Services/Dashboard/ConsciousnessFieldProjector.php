<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Services\Dashboard;

use App\Modules\Simulation\Core\Runtime\State\WorldState;

class ConsciousnessFieldProjector
{
    public function project(WorldState $state, int $universeId): array
    {
        $zones = $state->getZones();
        $resonance = (float) $state->get('resonance_field', 0.0);
        $fields = $state->getFields();

        $heatmap = [];
        foreach ($zones as $idx => $zone) {
            $localBias = (float) ($zone['state']['religious_pressure'] ?? 0.0) * 0.4 +
                         (float) ($zone['state']['innovation_pressure'] ?? 0.0) * 0.3;

            $heatmap[] = [
                'zone_id' => $idx,
                'x' => $zone['x'] ?? ($idx % 5),
                'y' => $zone['y'] ?? floor($idx / 5),
                'intensity' => round(min(1.0, $resonance * 0.6 + $localBias), 4),
                'phase' => $resonance > 0.8 ? 'APOTHEOSIS' : ($resonance > 0.4 ? 'AWAKENING' : 'DORMANT'),
            ];
        }

        return [
            'universe_id' => $universeId,
            'global_resonance' => $resonance,
            'primary_dimension' => !empty($fields) ? (array_search(max($fields), $fields) ?: 'none') : 'none',
            'heatmap' => $heatmap,
        ];
    }
}
