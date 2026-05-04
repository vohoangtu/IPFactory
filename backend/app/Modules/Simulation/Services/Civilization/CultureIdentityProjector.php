<?php

namespace App\Modules\Simulation\Services\Civilization;

use App\Modules\Intelligence\Services\CultureEngine as ActorCultureEngine;

/**
 * Projects zone-level culture profiles into a universe-facing cultural identity summary.
 */
class CultureIdentityProjector
{
    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function projectFromState(array $state): array
    {
        $zones = array_values(array_filter((array) ($state['zones'] ?? []), fn ($zone) => is_array($zone)));

        if ($zones === []) {
            return [
                'zone_count' => 0,
                'dominant_group' => 'unknown',
                'group_diversity' => 0,
                'average_cohesion' => 0.0,
                'dominant_memes' => [],
            ];
        }

        $groupCounts = [];
        $memeTotals = array_fill_keys(ActorCultureEngine::MEME_DIMENSIONS, 0.0);
        $cohesionTotal = 0.0;
        $profileCount = 0;

        foreach ($zones as $zone) {
            $state = (array) ($zone['state'] ?? []);
            $profile = (array) ($state['culture_profile'] ?? []);
            
            // Heuristic Fallback: If no culture profile, guess it from raw zone data
            if ($profile === []) {
                $profile = $this->deriveHeuristicProfile($zone);
            }

            if ($profile === []) {
                continue;
            }

            $group = (string) ($profile['dominant_group'] ?? 'ungrouped');
            $groupCounts[$group] = ($groupCounts[$group] ?? 0) + 1;
            $cohesionTotal += (float) ($profile['cohesion'] ?? 0.0);
            $profileCount++;

            foreach ((array) ($profile['meme_signature'] ?? []) as $dimension => $value) {
                if (!array_key_exists($dimension, $memeTotals)) {
                    continue;
                }

                $memeTotals[$dimension] += (float) $value;
            }
        }

        arsort($groupCounts);

        $averages = [];
        $divisor = max(1, $profileCount);
        foreach ($memeTotals as $dimension => $total) {
            $averages[$dimension] = round($total / $divisor, 4);
        }

        arsort($averages);

        return [
            'zone_count' => count($zones),
            'dominant_group' => (string) (array_key_first($groupCounts) ?? 'ungrouped'),
            'group_diversity' => count($groupCounts),
            'average_cohesion' => round($cohesionTotal / $divisor, 4),
            'dominant_memes' => array_slice($averages, 0, 5, true),
        ];
    }

    /**
     * Derives a culture profile from raw zone metrics (heuristic).
     * @param array<string, mixed> $zone
     * @return array<string, mixed>
     */
    public function deriveHeuristicProfile(array $zone): array
    {
        $state = (array) ($zone['state'] ?? []);
        $stress = (float) ($state['stress'] ?? 0);
        $population = (int) ($state['energy_level'] ?? 10); // Assume energy correlates with pop for genesis
        $biome = (string) ($zone['biome'] ?? 'plains');

        $cohesion = max(0.1, 1.0 - ($stress / 100));
        
        // Base memes on environment
        $memes = [
            'collectivism' => 0.5,
            'tradition' => 0.5,
            'spirituality' => 0.5,
            'order' => 0.5,
        ];

        if ($stress > 50) {
            $memes['survivalism'] = 0.8;
            $memes['chaos'] = 0.4;
        }

        if (str_contains(strtolower($biome), 'ocean')) {
            $memes['fluidity'] = 0.7;
        }

        return [
            'dominant_group' => $population > 100 ? 'urban-clique' : 'kinship-band',
            'cohesion' => $cohesion,
            'meme_signature' => $memes,
            'is_heuristic' => true,
        ];
    }
}
