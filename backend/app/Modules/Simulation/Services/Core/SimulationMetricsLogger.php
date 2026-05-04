<?php

namespace App\Modules\Simulation\Services\Core;

use App\Modules\Simulation\Models\UniverseSnapshot;

/**
 * Computes simulation health metrics from snapshot state (doc 21 §4c).
 * Used for observability: mean/variance pressure, collapse rate, population variance, innovation proxy.
 * Formula for zone pressure matches Rust: 0.2*inequality + 0.3*entropy + 0.2*trauma + 0.3*material_stress.
 */
final class SimulationMetricsLogger
{
    private const PRESSURE_WEIGHTS = [
        'inequality' => 0.2,
        'entropy' => 0.3,
        'trauma' => 0.2,
        'material_stress' => 0.3,
    ];

    /**
     * Compute metrics from a single snapshot.
     *
     * @return array{mean_pressure: float, variance_pressure: float, collapse_rate: float, population_variance: float, innovation_proxy: float, zone_count: int, collapse_count: int}
     */
    public function fromSnapshot(UniverseSnapshot $snapshot): array
    {
        $state = is_array($snapshot->state_vector) ? $snapshot->state_vector : [];
        $zones = $state['zones'] ?? [];
        $zoneCount = is_array($zones) ? count($zones) : 0;

        if ($zoneCount === 0) {
            return [
                'mean_pressure' => 0.0,
                'variance_pressure' => 0.0,
                'collapse_rate' => 0.0,
                'population_variance' => 0.0,
                'innovation_proxy' => 0.0,
                'zone_count' => 0,
                'collapse_count' => 0,
            ];
        }

        $pressures = [];
        $populations = [];
        $innovations = [];
        $collapseCount = 0;

        foreach ($zones as $zone) {
            $st = $zone['state'] ?? [];
            $p = $this->pressureAtZone($st);
            $pressures[] = $p;

            $pop = (float) ($st['population_proxy'] ?? 0.5);
            $populations[] = $pop;

            $innovations[] = (float) ($st['embodied_knowledge'] ?? ($st['knowledge_frontier'] ?? 0));

            $phase = strtolower((string) ($st['cascade_phase'] ?? 'normal'));
            if ($phase === 'collapse') {
                $collapseCount++;
            }
        }

        $meanPressure = $this->mean($pressures);
        $variancePressure = $this->variance($pressures, $meanPressure);
        $popVariance = $this->variance($populations, $this->mean($populations));
        $innovationProxy = $this->mean($innovations);

        return [
            'mean_pressure' => round($meanPressure, 6),
            'variance_pressure' => round($variancePressure, 6),
            'collapse_rate' => $zoneCount > 0 ? round($collapseCount / $zoneCount, 6) : 0.0,
            'population_variance' => round($popVariance, 6),
            'innovation_proxy' => round($innovationProxy, 6),
            'zone_count' => $zoneCount,
            'collapse_count' => $collapseCount,
        ];
    }

    /**
     * Pressure at one zone (same formula as Rust pressure_at_zone).
     */
    public function pressureAtZone(array $state): float
    {
        $v = 0.0;
        foreach (self::PRESSURE_WEIGHTS as $key => $w) {
            $v += $w * ((float) ($state[$key] ?? 0));
        }
        return max(0.0, min(1.0, $v));
    }

    /**
     * Compute metrics for a range of ticks (e.g. for variance-over-time analysis).
     *
     * @return array<int, array{tick: int, mean_pressure: float, variance_pressure: float, collapse_rate: float, population_variance: float, innovation_proxy: float}>>
     */
    public function fromSnapshotRange(int $universeId, int $fromTick, int $toTick, int $every = 1): array
    {
        $out = [];
        $snapshots = UniverseSnapshot::where('universe_id', $universeId)
            ->whereBetween('tick', [$fromTick, $toTick])
            ->orderBy('tick')
            ->get();

        foreach ($snapshots as $snap) {
            $tick = (int) $snap->tick;
            if (($tick - $fromTick) % $every !== 0) {
                continue;
            }
            $m = $this->fromSnapshot($snap);
            $out[$tick] = array_merge(['tick' => $tick], $m);
        }

        return $out;
    }

    private function mean(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }
        return array_sum($values) / count($values);
    }

    private function variance(array $values, ?float $mean = null): float
    {
        if (empty($values)) {
            return 0.0;
        }
        $mean ??= $this->mean($values);
        $sqDiffs = array_map(fn ($x) => ($x - $mean) ** 2, $values);
        return array_sum($sqDiffs) / count($values);
    }
}


