<?php

namespace App\Modules\Simulation\Core\Support;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Runtime\State\WorldState;

/**
 * Builds immutable WorldState from Universe and UniverseSnapshot (or latest snapshot).
 * Ensures zone state includes Potential Field pressure keys (default 0) when present.
 */
final class SnapshotLoader
{
    public function fromSnapshot(Universe $universe, UniverseSnapshot $snapshot): WorldState
    {
        $metrics = is_array($snapshot->metrics) ? $snapshot->metrics : [];
        $stateVector = is_array($snapshot->state_vector) ? $snapshot->state_vector : [];
        $stateVector = $this->ensureZonePressureKeysInVector($stateVector);
        $stateVector = $this->ensureZonePopulationKeysInVector($stateVector);

        return new WorldState(
            (int) $universe->id,
            (int) $snapshot->tick,
            $metrics,
            $stateVector,
        );
    }

    /** Ensure each zone has state.war_pressure, economic_pressure, etc. (0 if missing). */
    private function ensureZonePressureKeysInVector(array $stateVector): array
    {
        $zones = $stateVector['zones'] ?? [];
        if (empty($zones)) {
            return $stateVector;
        }
        $defaults = WorldState::defaultZonePressureKeys();
        foreach ($zones as $i => $zone) {
            if (!isset($zone['state']) || !is_array($zone['state'])) {
                $zones[$i]['state'] = [];
            }
            foreach ($defaults as $key => $value) {
                if (!array_key_exists($key, $zones[$i]['state'])) {
                    $zones[$i]['state'][$key] = $value;
                }
            }
        }
        $stateVector['zones'] = $zones;
        return $stateVector;
    }

    /** Ensure each zone has state.population_proxy (population layer). */
    private function ensureZonePopulationKeysInVector(array $stateVector): array
    {
        $zones = $stateVector['zones'] ?? [];
        if (empty($zones)) {
            return $stateVector;
        }
        $defaults = WorldState::defaultZonePopulationKeys();
        foreach ($zones as $i => $zone) {
            if (!isset($zones[$i]['state']) || !is_array($zones[$i]['state'])) {
                $zones[$i]['state'] = [];
            }
            foreach ($defaults as $key => $value) {
                if (!array_key_exists($key, $zones[$i]['state'])) {
                    $zones[$i]['state'][$key] = $value;
                }
            }
        }
        $stateVector['zones'] = $zones;
        return $stateVector;
    }

    /** Load WorldState from universe using its latest snapshot. */
    public function fromUniverse(Universe $universe): ?WorldState
    {
        $snapshot = $universe->snapshots()->orderByDesc('tick')->first();
        if (!$snapshot) {
            return null;
        }
        return $this->fromSnapshot($universe, $snapshot);
    }
}

