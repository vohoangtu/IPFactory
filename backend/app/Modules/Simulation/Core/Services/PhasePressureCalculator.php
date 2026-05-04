<?php

namespace App\Modules\Simulation\Core\Services;

/**
 * Computes phase transition pressures from cosmic signals.
 * ascension_pressure: tendency toward Ascension (order + energy + spirituality).
 * collapse_pressure: tendency toward Eschaton (entropy + disorder).
 */
final class PhasePressureCalculator
{
    /**
     * @param array<string, float> $signals from CosmicSignalCollector::collect()
     * @return array{ascension_pressure: float, collapse_pressure: float}
     */
    public function calculate(array $signals): array
    {
        $order = (float) ($signals['order'] ?? 0);
        $energy = (float) ($signals['energy_level'] ?? 0);
        $entropy = (float) ($signals['entropy'] ?? 0);
        $spirituality = (float) ($signals['spirituality'] ?? 0);
        $violence = (float) ($signals['violence'] ?? 0);

        // Ascension: high order + energy + spirituality, low violence
        $ascension = ($order * 0.35) + ($energy * 0.35) + ($spirituality * 0.25) - ($violence * 0.15);
        $ascension = max(0.0, min(1.0, $ascension));

        // Collapse (Eschaton): high entropy, low order
        $collapse = ($entropy * 0.6) + ((1.0 - $order) * 0.4);
        $collapse = max(0.0, min(1.0, $collapse));

        return [
            'ascension_pressure' => round($ascension, 4),
            'collapse_pressure' => round($collapse, 4),
        ];
    }
}
