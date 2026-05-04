<?php

namespace App\Modules\Simulation\Core\Services;

use App\Modules\Simulation\Core\Runtime\State\WorldState;

/**
 * Collects cosmic-level signals from WorldState for Phase Pressure calculation.
 * Used by PhasePressureCalculator to compute ascension_pressure and collapse_pressure.
 */
final class CosmicSignalCollector
{
    /**
     * Collect raw signals from state (metrics + state_vector).
     *
     * @return array<string, float> order, energy_level, entropy, myth, spirituality, violence, innovation
     */
    public function collect(WorldState $state): array
    {
        $metrics = $state->getMetrics();
        $vec = $state->getStateVector();
        $pressures = $state->getPressures();

        return [
            'order' => (float) ($metrics['order'] ?? $vec['order'] ?? 0),
            'energy_level' => (float) ($metrics['energy_level'] ?? $vec['energy_level'] ?? 0),
            'entropy' => $state->getEntropy(),
            'myth' => (float) ($vec['myth'] ?? $pressures['myth'] ?? 0),
            'spirituality' => (float) ($vec['spirituality'] ?? 0),
            'violence' => (float) ($vec['violence'] ?? 0),
            'innovation' => $state->getInnovation(),
        ];
    }
}
