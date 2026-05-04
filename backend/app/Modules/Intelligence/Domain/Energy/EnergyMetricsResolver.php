<?php

namespace App\Modules\Intelligence\Domain\Energy;

/**
 * Ensure energy, max_energy, metabolism in metrics (backward compat).
 * Metabolism from physic: body_size proxy (avg physic) * 0.3 + strength * 0.2 + stamina * 0.1.
 */
class EnergyMetricsResolver
{
    /**
     * @param array<string, mixed> $metrics
     * @param array<string|int, mixed> $traits
     * @param array<string, mixed>|null $physic
     * @return array<string, mixed>
     */
    public function resolve(array $metrics, array $traits, ?array $physic, float $energyMaxDefault, float $metabolismBase): array
    {
        if (!isset($metrics['max_energy']) || $metrics['max_energy'] <= 0) {
            $metrics['max_energy'] = $energyMaxDefault;
        }
        if (!array_key_exists('energy', $metrics) || $metrics['energy'] === null) {
            $metrics['energy'] = (float) ($metrics['max_energy'] ?? $energyMaxDefault);
        }
        if (!isset($metrics['metabolism'])) {
            $physicAggregate = 0.5;
            if ($physic !== null && $physic !== []) {
                $vals = array_values($physic);
                $n = 0;
                $sum = 0;
                foreach ($vals as $v) {
                    if (is_numeric($v)) {
                        $sum += max(0, min(1, (float) $v));
                        $n++;
                    }
                }
                $physicAggregate = $n > 0 ? $sum / $n : 0.5;
            }
            $strength = (float) ($physic[2] ?? $physic['Strength'] ?? $physicAggregate);
            $stamina = (float) ($physic[1] ?? $physic['Stamina'] ?? $physicAggregate);
            $metrics['metabolism'] = $metabolismBase * (0.6 + 0.2 * $physicAggregate + 0.1 * $strength + 0.1 * $stamina);
        }
        return $metrics;
    }
}
