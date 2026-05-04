<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Services\Dashboard;

use App\Modules\Simulation\Core\Runtime\State\WorldState;

class InformationalMassProjector
{
    public function project(WorldState $state, int $universeId): array
    {
        $density = (float) $state->get('meta.information_density', 0.0);
        $entropy = $state->getEntropy();
        $fields = $state->getFields();
        $tick = (int) $state->get('tick', 0);

        $fieldMass = array_sum(array_map(fn($v) => max(0, $v * (1 - $entropy)), $fields));
        $totalMass = $fieldMass * (1 + $density);

        return [
            'universe_id' => $universeId,
            'tick' => $tick,
            'informational_mass' => round($totalMass, 4),
            'information_density' => $density,
            'field_contributions' => array_map(
                fn($k, $v) => ['field' => $k, 'mass' => round(max(0, $v * (1 - $entropy)), 4)],
                array_keys($fields),
                array_values($fields)
            ),
            'singularity_risk' => $density > 0.95 ? 'CRITICAL' : ($density > 0.8 ? 'HIGH' : 'NORMAL'),
        ];
    }
}
