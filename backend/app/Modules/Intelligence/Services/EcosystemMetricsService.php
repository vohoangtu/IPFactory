<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;

/**
 * Ecosystem metrics for Ecological Collapse Engine: population, species, resources, biodiversity, instability.
 */
class EcosystemMetricsService
{
    public function __construct(
        private BiologyMetricsService $biologyMetrics
    ) {}

    /**
     * Full ecosystem metrics + instability_score for a universe.
     *
     * @return array{
     *   total_population: int,
     *   species_count: int,
     *   resource_level: float,
     *   biodiversity_index: float,
     *   predator_ratio: float,
     *   resource_stress: float,
     *   instability_score: float
     * }
     */
    public function forUniverse(Universe $universe): array
    {
        $bio = $this->biologyMetrics->forUniverse($universe->id);
        $totalPopulation = $bio['total_alive'];
        $speciesCount = $bio['species_count'];
        $speciesDistribution = $bio['species_distribution'] ?? [];

        $resourceLevel = $this->getResourceLevel($universe);
        $biodiversityIndex = $this->computeBiodiversityIndex($totalPopulation, $speciesDistribution);
        $predatorRatio = 0.0; // No predator/prey distinction yet

        $totalFood = $this->getTotalFood($universe);
        $resourceStress = $totalPopulation > 0 && $totalFood > 0
            ? min(1.0, ($totalPopulation / 5.0) / max(0.1, $totalFood))
            : ($totalPopulation > 0 ? 1.0 : 0.0);

        $instabilityScore = $resourceStress * 0.4 + $predatorRatio * 0.3 + (1.0 - $biodiversityIndex) * 0.3;
        $instabilityScore = max(0.0, min(1.0, $instabilityScore));

        return [
            'total_population' => $totalPopulation,
            'species_count' => $speciesCount,
            'resource_level' => round($resourceLevel, 4),
            'biodiversity_index' => round($biodiversityIndex, 4),
            'predator_ratio' => $predatorRatio,
            'resource_stress' => round($resourceStress, 4),
            'instability_score' => round($instabilityScore, 4),
        ];
    }

    private function getResourceLevel(Universe $universe): float
    {
        $totalFood = $this->getTotalFood($universe);
        $zones = $this->getZones($universe);
        $n = is_array($zones) ? count($zones) : 0;
        if ($n === 0) {
            return 0.5;
        }
        return min(1.0, $totalFood / max(1, $n * 10));
    }

    private function getTotalFood(Universe $universe): float
    {
        $zones = $this->getZones($universe);
        if (!is_array($zones)) {
            return 1.0;
        }
        $total = 0.0;
        foreach ($zones as $zone) {
            $state = $zone['state'] ?? $zone;
            $total += (float) ($state['food'] ?? $state['resources'] ?? 0.5);
        }
        return $total;
    }

    private function getZones(Universe $universe): array
    {
        $stateVector = $universe->state_vector;
        if (is_string($stateVector)) {
            $stateVector = json_decode($stateVector, true) ?? [];
        }
        $zones = $stateVector['zones'] ?? [];
        return is_array($zones) ? $zones : [];
    }

    /**
     * Biodiversity: species_count / max(1, total_population) or Shannon entropy.
     */
    private function computeBiodiversityIndex(int $totalPopulation, array $speciesDistribution): float
    {
        if ($totalPopulation <= 0) {
            return 0.0;
        }
        $n = array_sum($speciesDistribution);
        if ($n <= 0) {
            return 0.0;
        }
        $h = 0.0;
        foreach ($speciesDistribution as $count) {
            $p = $count / $n;
            if ($p > 0) {
                $h -= $p * log($p + 1e-10);
            }
        }
        $maxH = log(max(1, count($speciesDistribution)) + 1e-10);
        if ($maxH <= 0) {
            return 1.0;
        }
        return min(1.0, $h / $maxH);
    }
}

