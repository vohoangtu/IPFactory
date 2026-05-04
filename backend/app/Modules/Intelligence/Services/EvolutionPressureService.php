<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;

/**
 * Computes environment pressure vector from universe state (zones).
 * Used for fitness: survival_probability *= fitness, reproduction_probability *= fitness.
 */
class EvolutionPressureService
{
    /**
     * Pressure vector (0–1). High = harsh.
     * food_pressure: 1 - resource availability
     * competition_pressure: population density proxy
     * predator_pressure, climate_pressure: reserved for future use.
     *
     * @return array{food_pressure: float, predator_pressure: float, climate_pressure: float, competition_pressure: float}
     */
    public function fromUniverse(Universe $universe): array
    {
        $stateVector = $universe->state_vector;
        if (is_string($stateVector)) {
            $stateVector = json_decode($stateVector, true) ?? [];
        }
        $zones = $stateVector['zones'] ?? [];
        if (!is_array($zones) || empty($zones)) {
            return [
                'food_pressure' => 0.5,
                'predator_pressure' => 0.0,
                'climate_pressure' => 0.0,
                'competition_pressure' => 0.5,
            ];
        }

        $totalFood = 0.0;
        $totalPopulation = 0.0;
        $n = 0;
        foreach ($zones as $zone) {
            $state = $zone['state'] ?? $zone;
            $food = (float) ($state['food'] ?? $state['resources'] ?? 0.5);
            $pop = (float) ($state['population_proxy'] ?? 0.1);
            $totalFood += $food;
            $totalPopulation += $pop;
            $n++;
        }
        $avgFood = $n > 0 ? $totalFood / $n : 0.5;
        $avgPop = $n > 0 ? $totalPopulation / $n : 0.1;
        // food_pressure: low food -> high pressure (1 - normalized food)
        $foodPressure = 1.0 - min(1.0, $avgFood);
        // competition: higher population -> higher pressure (cap)
        $competitionPressure = min(1.0, $avgPop * 2);

        // Optional: evolution shock (mass extinction event) amplifies pressure
        $shock = $stateVector['evolution_shock'] ?? null;
        if (is_array($shock)) {
            $foodPressure = min(1.0, $foodPressure * (1 + (float) ($shock['food'] ?? 0)));
            $competitionPressure = min(1.0, $competitionPressure * (1 + (float) ($shock['competition'] ?? 0)));
        }
        // Ecological collapse: when active, amplify pressure for narrative/fitness context
        $ecologicalCollapse = $stateVector['ecological_collapse'] ?? null;
        if (is_array($ecologicalCollapse) && !empty($ecologicalCollapse['active'])) {
            $foodPressure = min(1.0, $foodPressure * 1.2);
            $competitionPressure = min(1.0, $competitionPressure * 1.1);
        }

        return [
            'food_pressure' => $foodPressure,
            'predator_pressure' => 0.0,
            'climate_pressure' => 0.0,
            'competition_pressure' => $competitionPressure,
        ];
    }

    /**
     * Helper to load universe and compute pressure from ID.
     */
    public function fromUniverseId(int $universeId): array
    {
        $universe = Universe::find($universeId);
        if (!$universe) {
            return [
                'food_pressure' => 0.5,
                'predator_pressure' => 0.0,
                'climate_pressure' => 0.0,
                'competition_pressure' => 0.5,
            ];
        }
        return $this->fromUniverse($universe);
    }

    /**
     * Fitness score 0.2–1.0 from actor genome (traits, physic) and pressure.
     * Higher metabolism under food pressure -> lower fitness; resilience/cooperation under competition -> higher fitness.
     */
    public function fitness(array $traits, ?array $physic, array $pressure): float
    {
        $metabolismProxy = 0.5;
        if ($physic !== null && $physic !== []) {
            $v = array_values(array_filter($physic, 'is_numeric'));
            $metabolismProxy = $v ? array_sum($v) / count($v) : 0.5;
        }
        $resilience = (float) ($traits[10] ?? $traits['RiskTolerance'] ?? 0.5);
        $solidarity = (float) ($traits[5] ?? $traits['Solidarity'] ?? 0.5);

        $foodPressure = $pressure['food_pressure'] ?? 0.5;
        $competitionPressure = $pressure['competition_pressure'] ?? 0.5;

        $f = 1.0
            - 0.4 * $foodPressure * $metabolismProxy
            + 0.2 * (1 - $competitionPressure) * $solidarity
            + 0.15 * (1 - $foodPressure) * $resilience;
        return max(0.2, min(1.0, $f));
    }

    /**
     * Coarse species id from genome (physic + traits) for visualization. Similar genome -> same id.
     */
    public function speciesId(array $traits, ?array $physic): string
    {
        $physic = $physic ?? [];
        $vals = [];
        foreach (array_values($physic) as $v) {
            $vals[] = is_numeric($v) ? round(max(0, min(1, (float) $v)), 1) : 0.5;
        }
        $traitSlice = array_slice(array_values($traits), 0, 5);
        foreach ($traitSlice as $v) {
            $vals[] = is_numeric($v) ? round(max(0, min(1, (float) $v)), 1) : 0.5;
        }
        return 'S' . substr(md5(json_encode($vals)), 0, 8);
    }
}

