<?php

namespace App\Modules\Simulation\Core\Engines\Physics;

use App\Modules\Simulation\Core\Runtime\State\WorldState;

/**
 * Computes zone-level pressure deltas from zone state, global state, and neighbor pressures.
 * Used by PotentialFieldEngine; does not touch DB.
 */
final class ZonePressureCalculator
{
    /** @return array<string, float> deltas for war_pressure, economic_pressure, religious_pressure, migration_pressure, innovation_pressure */
    public function computeDeltas(array $zone, array $globalState, array $neighborPressures): array
    {
        $state = $zone['state'] ?? [];
        $entropy = (float) ($state['entropy'] ?? 0);
        $order = (float) ($state['order'] ?? 0);
        $culture = $state['culture'] ?? [];
        $tradition = (float) ($culture['tradition'] ?? 0);
        $innovation = (float) ($culture['innovation'] ?? 0);
        $myth = (float) ($culture['myth'] ?? 0);

        $globalEntropy = (float) ($globalState['entropy'] ?? ($globalState['metrics']['entropy'] ?? 0.5));
        $globalOrder = (float) ($globalState['order'] ?? ($globalState['metrics']['order'] ?? 0.5));

        $deltas = [
            'war_pressure' => 0.0,
            'economic_pressure' => 0.0,
            'religious_pressure' => 0.0,
            'migration_pressure' => 0.0,
            'innovation_pressure' => 0.0,
        ];

        // Border tension: high entropy neighbor + order imbalance -> war_pressure
        $avgNeighborWar = $this->avgNeighborPressure($neighborPressures, 'war_pressure');
        $deltas['war_pressure'] += $avgNeighborWar * 0.15;
        if ($order < 0.4 && $entropy > 0.5) {
            $deltas['war_pressure'] += 0.02; // instability
        }
        // Culture clash proxy: high violence/tradition variance vs innovation
        $violence = (float) ($culture['violence'] ?? 0);
        if ($violence > 0.5 && $tradition > 0.6) {
            $deltas['war_pressure'] += 0.015;
        }

        // Economic: low order, high entropy -> scarcity; population as source of pressure
        $populationProxy = (float) ($state['population_proxy'] ?? 0.5);
        $deltas['economic_pressure'] += $populationProxy * 0.015;
        if ($order < 0.5) {
            $deltas['economic_pressure'] += 0.02;
        }
        if ($globalOrder < 0.4) {
            $deltas['economic_pressure'] += 0.01;
        }
        $avgEcon = $this->avgNeighborPressure($neighborPressures, 'economic_pressure');
        $deltas['economic_pressure'] += $avgEcon * 0.1;

        // Religious: myth, tradition
        $deltas['religious_pressure'] += $myth * 0.02 + $tradition * 0.01;
        $avgRel = $this->avgNeighborPressure($neighborPressures, 'religious_pressure');
        $deltas['religious_pressure'] += $avgRel * 0.1;

        // Migration: economic pressure pulls migration
        $deltas['migration_pressure'] += $deltas['economic_pressure'] * 0.5;
        $avgMig = $this->avgNeighborPressure($neighborPressures, 'migration_pressure');
        $deltas['migration_pressure'] += $avgMig * 0.1;

        // Innovation: from zone culture.innovation and global
        $globalInnovation = (float) ($globalState['innovation'] ?? ($globalState['metrics']['innovation'] ?? 0));
        $deltas['innovation_pressure'] += $innovation * 0.02 + $globalInnovation * 0.01;
        $avgInn = $this->avgNeighborPressure($neighborPressures, 'innovation_pressure');
        $deltas['innovation_pressure'] += $avgInn * 0.1;

        return $deltas;
    }

    /**
     * Field coupling: pressures influence each other (e.g. war → economic stress, religious → migration).
     *
     * @param array<string, float> $pressures
     * @return array<string, float>
     */
    public function applyCoupling(array $pressures): array
    {
        $war = (float) ($pressures['war_pressure'] ?? 0);
        $econ = (float) ($pressures['economic_pressure'] ?? 0);
        $rel = (float) ($pressures['religious_pressure'] ?? 0);
        $mig = (float) ($pressures['migration_pressure'] ?? 0);
        $inn = (float) ($pressures['innovation_pressure'] ?? 0);

        $econ += $war * 0.08;
        $mig += $econ * 0.06 + $rel * 0.04;
        $inn = max(0, $inn - $war * 0.05);

        return [
            'war_pressure' => max(0.0, min(1.0, $war)),
            'economic_pressure' => max(0.0, min(1.0, $econ)),
            'religious_pressure' => max(0.0, min(1.0, $rel)),
            'migration_pressure' => max(0.0, min(1.0, $mig)),
            'innovation_pressure' => max(0.0, min(1.0, $inn)),
        ];
    }

    /** @param array<int, array<string, float>> $neighborPressures list of pressure maps from WorldState::getZonePressures */
    private function avgNeighborPressure(array $neighborPressures, string $key): float
    {
        if (empty($neighborPressures)) {
            return 0.0;
        }
        $sum = 0.0;
        foreach ($neighborPressures as $p) {
            $sum += (float) ($p[$key] ?? 0);
        }
        return $sum / count($neighborPressures);
    }

    /** Ensure zone state has all pressure keys (default 0). Modifies zone in place. */
    public function ensureZonePressureKeys(array &$zone): void
    {
        $state = &$zone['state'];
        if (!is_array($state)) {
            $state = [];
        }
        foreach (WorldState::defaultZonePressureKeys() as $key => $value) {
            if (!array_key_exists($key, $state)) {
                $state[$key] = $value;
            }
        }
        foreach (WorldState::defaultZonePopulationKeys() as $key => $value) {
            if (!array_key_exists($key, $state)) {
                $state[$key] = $value;
            }
        }
    }
}
