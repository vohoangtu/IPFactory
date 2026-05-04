<?php

namespace App\Modules\Simulation\Services\Cosmology;

use Illuminate\Support\Facades\DB;

/**
 * Reality Calibration (Doc §33): historical benchmarks and auto-calibration loop placeholder.
 */
final class RealityCalibrationService
{
    public function getBenchmarks(): array
    {
        if (! $this->tableExists()) {
            return [];
        }
        return DB::table('calibration_benchmarks')->pluck('value', 'key')->toArray();
    }

    public function compareWithBenchmarks(array $simulationMetrics): array
    {
        $benchmarks = $this->getBenchmarks();
        $deltas = [];
        foreach ($benchmarks as $key => $target) {
            $actual = $simulationMetrics[$key] ?? null;
            if ($actual !== null) {
                $deltas[$key] = ['target' => $target, 'actual' => $actual, 'delta' => $actual - $target];
            }
        }
        return $deltas;
    }

    /**
     * From compareWithBenchmarks deltas, return suggested adjustments (no auto-apply).
     *
     * @param  array<string, array{target: float, actual: float, delta: float}>  $deltas
     * @return array<int, array{key: string, target: float, actual: float, delta: float, suggested_direction: string, suggested_factor: float}>
     */
    public function suggestAdjustments(array $deltas): array
    {
        $suggestions = [];
        foreach ($deltas as $key => $row) {
            $target = (float) ($row['target'] ?? 0);
            $actual = (float) ($row['actual'] ?? 0);
            $delta = (float) ($row['delta'] ?? 0);
            $direction = $delta > 0 ? 'decrease' : ($delta < 0 ? 'increase' : 'hold');
            $magnitude = abs($delta);
            $factor = $magnitude > 0.1 ? 1.15 : ($magnitude > 0.01 ? 1.05 : 1.0);
            $suggestions[] = [
                'key' => $key,
                'target' => $target,
                'actual' => $actual,
                'delta' => $delta,
                'suggested_direction' => $direction,
                'suggested_factor' => $direction === 'hold' ? 1.0 : $factor,
            ];
        }
        return $suggestions;
    }

    private function tableExists(): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasTable('calibration_benchmarks');
        } catch (\Throwable) {
            return false;
        }
    }
}
