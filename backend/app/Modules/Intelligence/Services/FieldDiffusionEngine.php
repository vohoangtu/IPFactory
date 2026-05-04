<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\Simulation\Core\Services\TopologyResolver;

/**
 * Phase 29: Field Diffusion Engine.
 * Propagates attractor fields (S, P, W, K, M) between adjacent zones.
 */
class FieldDiffusionEngine
{
    public function __construct(
        private readonly TopologyResolver $topologyResolver
    ) {}

    /**
     * Propagate fields between zones based on diffusion_rate from genome.
     * 
     * @param array &$zones The 'zones' array from state_vector
     * @param array $genome Simulation genome (contains diffusion_rate)
     */
    public function diffuse(array &$zones, array $genome): void
    {
        $diffusionRate = (float) ($genome['diffusion_rate'] ?? 0.1);
        if ($diffusionRate <= 0) return;

        $newZonesState = [];
        $fieldKeys = [
            'belief_field', 'ideology_field', 'knowledge_field',
            'power_field', 'authority_field', 'fear_field',
            'order_field', 'entropy_field', 'conflict_field',
            'resonance_field'
        ];

        foreach ($zones as $idx => $zone) {
            $currentFields = $zone['state']['civ_fields'] ?? null;
            if (!$currentFields) continue;

            $neighborIndices = $this->topologyResolver->getNeighborIndices($zones, $idx);
            
            $deltas = array_fill_keys($fieldKeys, 0.0);
            $validNeighbors = 0;

            foreach ($neighborIndices as $nIdx) {
                $neighborFields = $zones[$nIdx]['state']['civ_fields'] ?? null;
                if (!$neighborFields) continue;

                $validNeighbors++;
                foreach ($fieldKeys as $key) {
                    $deltas[$key] += ($neighborFields[$key] ?? 0.0) - ($currentFields[$key] ?? 0.0);
                }
            }

            if ($validNeighbors > 0) {
                $updatedFields = $currentFields;
                foreach ($fieldKeys as $key) {
                    // Update field: F_i = F_i + beta * (sum(F_j - F_i) / N)
                    $updatedFields[$key] += $diffusionRate * ($deltas[$key] / $validNeighbors);
                    $updatedFields[$key] = round(max(0.0, min(1.0, $updatedFields[$key])), 4);
                }
                $newZonesState[$idx] = $updatedFields;
            }
        }

        // Apply new states to avoid order-of-operation bias within the same tick
        foreach ($newZonesState as $idx => $fields) {
            $zones[$idx]['state']['civ_fields'] = $fields;
        }
    }
}

