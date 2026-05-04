<?php

namespace App\Modules\Simulation\Core\Services;

/**
 * Resolves zone topology: graph (neighbors from zone data) or ring fallback.
 * Used by PotentialFieldEngine and CulturalDriftEngine for diffusion and neighbor lookups.
 */
final class TopologyResolver
{
    /**
     * Resolve neighbor zone indices for the zone at $zoneIndex.
     * If zone has 'neighbors' (array of zone ids), map those ids to indices; else ring (prev, next).
     *
     * @param array<int, array> $zones list of zones (each may have 'id', 'neighbors')
     * @return int[] neighbor indices (for array key access into $zones)
     */
    public function getNeighborIndices(array $zones, int $zoneIndex): array
    {
        $count = count($zones);
        if ($count === 0) {
            return [];
        }

        $zone = $zones[$zoneIndex] ?? null;
        if ($zone === null) {
            return [];
        }

        $neighborIds = $zone['neighbors'] ?? null;
        if (is_array($neighborIds) && !empty($neighborIds)) {
            $idToIndex = $this->buildIdToIndex($zones);
            $indices = [];
            foreach ($neighborIds as $id) {
                $idx = $idToIndex[$id] ?? null;
                if ($idx !== null && $idx !== $zoneIndex) {
                    $indices[] = $idx;
                }
            }
            if (!empty($indices)) {
                return array_values(array_unique($indices));
            }
        }

        // Fallback: ring topology
        $prev = ($zoneIndex - 1 + $count) % $count;
        $next = ($zoneIndex + 1) % $count;
        return $prev !== $next ? [$prev, $next] : [$prev];
    }

    /**
     * Neighbor indices with optional weights for weighted diffusion.
     * If zone has 'edge_weights' => [ neighbor_id => weight ], use it; else weight 1.0.
     *
     * @param array<int, array> $zones
     * @return array<int, array{0: int, 1: float}> list of [neighborIndex, weight]
     */
    public function getNeighborIndicesWithWeights(array $zones, int $zoneIndex): array
    {
        $indices = $this->getNeighborIndices($zones, $zoneIndex);
        $zone = $zones[$zoneIndex] ?? null;
        $edgeWeights = $zone['edge_weights'] ?? null;
        $idToIndex = $this->buildIdToIndex($zones);

        $result = [];
        foreach ($indices as $nIdx) {
            $weight = 1.0;
            if (is_array($edgeWeights)) {
                $nId = $zones[$nIdx]['id'] ?? $nIdx;
                $weight = (float) ($edgeWeights[$nId] ?? $edgeWeights[(string) $nId] ?? 1.0);
            }
            $result[] = [$nIdx, max(0.01, $weight)];
        }
        return $result;
    }

    /**
     * @param array<int, array> $zones
     * @return array<int|string, int> id => index (supports string ids from Rust)
     */
    private function buildIdToIndex(array $zones): array
    {
        $map = [];
        foreach ($zones as $idx => $z) {
            $id = $z['id'] ?? $idx;
            $map[$id] = $idx;
        }
        return $map;
    }
}
