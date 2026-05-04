<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\Intelligence\Contracts\ActorRepositoryInterface;

/**
 * Aggregates biological and evolution metrics for dashboard (energy distribution, starvation, species).
 */
class BiologyMetricsService
{
    public function __construct(
        private ActorRepositoryInterface $actorRepository
    ) {}

    /**
     * @return array{avg_energy: float, median_energy: float, starving_count: int, total_alive: int, species_count: int, species_distribution: array<string, int>, trait_avg: array}
     */
    public function forUniverse(int $universeId): array
    {
        $actors = $this->actorRepository->findByUniverse($universeId);
        $alive = array_filter($actors, fn($a) => $a->isAlive);
        $energies = [];
        $starvingCount = 0;
        $speciesIds = [];
        $traitSums = [];
        $traitCounts = [];

        foreach ($alive as $actor) {
            $metrics = $actor->metrics ?? [];
            $e = isset($metrics['energy']) ? (float) $metrics['energy'] : null;
            if ($e !== null) {
                $energies[] = $e;
            }
            if (!empty($metrics['starving'])) {
                $starvingCount++;
            }
            $sid = $metrics['species_id'] ?? null;
            if ($sid !== null) {
                $speciesIds[$sid] = ($speciesIds[$sid] ?? 0) + 1;
            }
            $traits = $actor->traits ?? [];
            foreach ($traits as $i => $v) {
                if (is_numeric($v)) {
                    $traitSums[$i] = ($traitSums[$i] ?? 0) + (float) $v;
                    $traitCounts[$i] = ($traitCounts[$i] ?? 0) + 1;
                }
            }
        }

        sort($energies);
        $n = count($energies);
        $avgEnergy = $n > 0 ? array_sum($energies) / $n : 0.0;
        $medianEnergy = $n > 0 ? ($energies[(int) floor(($n - 1) / 2)] + $energies[(int) ceil(($n - 1) / 2)]) / 2 : 0.0;

        $traitAvg = [];
        foreach ($traitSums as $i => $sum) {
            $traitAvg[$i] = $traitCounts[$i] > 0 ? $sum / $traitCounts[$i] : 0.5;
        }

        return [
            'avg_energy' => round($avgEnergy, 2),
            'median_energy' => round($medianEnergy, 2),
            'starving_count' => $starvingCount,
            'total_alive' => count($alive),
            'species_count' => count($speciesIds),
            'species_distribution' => $speciesIds,
            'trait_avg' => $traitAvg,
        ];
    }
}
