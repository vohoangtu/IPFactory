<?php

namespace App\Modules\Simulation\Services\Ecology;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Repositories\UniverseRepository;
use Illuminate\Support\Facades\Config;

/**
 * Urban stress and agriculture capacity (Doc §14, §15). Writes state_vector.civilization.settlements[].urban_stress
 * and state_vector.agriculture_capacity (per-zone or aggregate). Called from post-snapshot or stage.
 */
final class UrbanStressAgricultureService
{
    public function __construct(
        protected UniverseRepository $universeRepository
    ) {}

    public function update(Universe $universe): void
    {
        $vec = is_array($universe->state_vector) ? $universe->state_vector : [];
        $civ = $vec['civilization'] ?? [];
        $settlements = $civ['settlements'] ?? [];
        $demographic = $civ['demographic'] ?? [];
        $urbanRatio = (float) ($demographic['urban_ratio_proxy'] ?? 0.5);
        $entropy = (float) ($vec['entropy'] ?? $universe->entropy ?? 0.5);
        $economy = $civ['economy'] ?? [];
        $inequality = is_array($economy) ? ($economy['inequality']['gini_index'] ?? 0.3) : 0.3;
        if (is_numeric($economy)) {
            $inequality = (float) $economy; // Handle legacy case if economy was just a scalar
        }

        $agricultureCapacity = [];
        foreach ($settlements as $zoneIndex => $s) {
            $population = (float) ($s['population'] ?? 0);
            $infra = $s['infrastructure'] ?? [];
            $water = (float) ($infra['water_supply'] ?? 0.5);
            $stress = $this->computeUrbanStress($population, $urbanRatio, $entropy, $inequality, $water);
            $settlements[$zoneIndex]['urban_stress'] = round($stress, 3);
            $agricultureCapacity[$zoneIndex] = $this->computeAgricultureCapacity($s, $entropy);
        }
        $civ['settlements'] = $settlements;
        $vec['civilization'] = $civ;
        $vec['agriculture_capacity'] = [
            'per_zone' => $agricultureCapacity,
            'aggregate' => count($agricultureCapacity) > 0 ? array_sum($agricultureCapacity) / count($agricultureCapacity) : 0.0,
            'updated_tick' => $universe->current_tick ?? 0,
        ];
        $this->universeRepository->update($universe->id, ['state_vector' => $vec]);
    }

    private function computeUrbanStress(float $population, float $urbanRatio, float $entropy, float $inequality, float $waterSupply): float
    {
        $popFactor = min(1.0, $population / 1000.0);
        $stress = 0.2 * $popFactor + 0.3 * $entropy + 0.2 * $inequality + 0.2 * (1.0 - $waterSupply) + 0.1 * $urbanRatio;
        return min(1.0, max(0.0, $stress));
    }

    private function computeAgricultureCapacity(array $settlement, float $entropy): float
    {
        $infra = $settlement['infrastructure'] ?? [];
        $water = (float) ($infra['water_supply'] ?? 0.5);
        $base = ($settlement['resource_capacity'] ?? 0.5);
        if (is_array($base)) {
            $base = (float) ($base['food'] ?? $base[0] ?? 0.5);
        }
        $capacity = (float) $base * (0.5 + 0.5 * $water) * (1.0 - 0.3 * $entropy);
        return round(min(1.0, max(0.0, $capacity)), 3);
    }
}

