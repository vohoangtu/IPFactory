<?php

namespace App\Modules\Simulation\Services\Ecology;

/**
 * Deep Sim Phase A: provides resource_capacity per zone for Rust kernel (population pressure).
 * Source: config worldos.geography.resource_capacity (zone_id => 0.0–1.0), or deterministic formula.
 */
class GeographyResourceService
{
    /**
     * Returns zone_id => capacity (0.0–1.0) for the given zones.
     *
     * @param  array<int, array{id?: int}>  $zones  Zone arrays with at least 'id' (or key as index).
     * @return array<int, float>
     */
    public function getResourceCapacityForZones(array $zones, int $universeId = 0): array
    {
        $config = config('worldos.geography.resource_capacity', []);
        $result = [];
        foreach ($zones as $idx => $zone) {
            $zoneId = (int) ($zone['id'] ?? $idx);
            if (is_array($config) && array_key_exists($zoneId, $config)) {
                $result[$zoneId] = max(0.0, min(1.0, (float) $config[$zoneId]));
            } else {
                $result[$zoneId] = max(0.0, min(1.0, 0.3 + 0.2 * ($zoneId % 3)));
            }
        }
        return $result;
    }
}

