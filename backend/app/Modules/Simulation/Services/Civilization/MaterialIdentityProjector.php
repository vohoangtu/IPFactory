<?php

namespace App\Modules\Simulation\Services\Civilization;

/**
 * Projects per-zone material profiles into a lightweight civilization-facing summary.
 * This is the first read-model layer for dossier/history/publishing features.
 */
class MaterialIdentityProjector
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
                'distinct_material_count' => 0,
                'primary_material' => 'unknown',
                'primary_livelihood' => 'unknown',
                'primary_settlement_style' => 'unknown',
                'dominant_materials' => [],
                'dominant_livelihoods' => [],
                'settlement_styles' => [],
                'climate_signatures' => [],
                'resource_biases' => [],
            ];
        }

        $materials = [];
        $livelihoods = [];
        $settlements = [];
        $climates = [];
        $biases = [];

        foreach ($zones as $zone) {
            $state = (array) ($zone['state'] ?? []);
            $profile = (array) ($state['material_profile'] ?? []);
            
            // Heuristic Fallback: If no material profile, guess it from raw zone data
            if ($profile === []) {
                $profile = $this->deriveHeuristicProfile($zone);
            }

            if ($profile === []) {
                continue;
            }

            $this->accumulate($materials, (string) ($profile['dominant_material'] ?? 'unknown'));
            $this->accumulate($livelihoods, (string) ($profile['livelihood'] ?? 'unknown'));
            $this->accumulate($settlements, (string) ($profile['settlement_style'] ?? 'unknown'));
            $this->accumulate($climates, (string) ($profile['climate_signature'] ?? 'unknown'));
            $this->accumulate($biases, (string) ($profile['resource_bias'] ?? 'unknown'));
        }

        arsort($materials);
        arsort($livelihoods);
        arsort($settlements);
        arsort($climates);
        arsort($biases);

        return [
            'zone_count' => count($zones),
            'distinct_material_count' => count($materials),
            'primary_material' => (string) (array_key_first($materials) ?? 'unknown'),
            'primary_livelihood' => (string) (array_key_first($livelihoods) ?? 'unknown'),
            'primary_settlement_style' => (string) (array_key_first($settlements) ?? 'unknown'),
            'dominant_materials' => array_slice($materials, 0, 5, true),
            'dominant_livelihoods' => array_slice($livelihoods, 0, 5, true),
            'settlement_styles' => array_slice($settlements, 0, 5, true),
            'climate_signatures' => array_slice($climates, 0, 5, true),
            'resource_biases' => array_slice($biases, 0, 5, true),
        ];
    }

    /**
     * Derives a material profile from raw zone metrics (heuristic).
     * @param array<string, mixed> $zone
     * @return array<string, mixed>
     */
    public function deriveHeuristicProfile(array $zone): array
    {
        $biome = strtolower((string) ($zone['biome'] ?? 'plains'));
        
        $material = 'stone';
        $livelihood = 'foraging';
        $style = 'timber';
        $bias = 'generic';

        // Biome-based heuristics
        if (str_contains($biome, 'ocean') || str_contains($biome, 'coast')) {
            $material = 'shell/salt';
            $livelihood = 'fishing';
            $style = 'stilt';
            $bias = 'maritime';
        } elseif (str_contains($biome, 'mountain') || str_contains($biome, 'highland')) {
            $material = 'stone/iron';
            $livelihood = 'mining';
            $style = 'stone';
            $bias = 'mineral';
        } elseif (str_contains($biome, 'forest') || str_contains($biome, 'jungle')) {
            $material = 'timber/herb';
            $livelihood = 'foraging';
            $style = 'wood';
            $bias = 'biological';
        } elseif (str_contains($biome, 'desert') || str_contains($biome, 'waste')) {
            $material = 'sand/clay';
            $livelihood = 'nomadic';
            $style = 'adobe';
            $bias = 'scarcity';
        }

        return [
            'dominant_material' => $material,
            'livelihood' => $livelihood,
            'settlement_style' => $style,
            'climate_signature' => $biome,
            'resource_bias' => $bias,
            'is_heuristic' => true,
        ];
    }

    /**
     * @param array<string, int> $bucket
     */
    private function accumulate(array &$bucket, string $key): void
    {
        $normalized = trim($key) !== '' ? $key : 'unknown';
        $bucket[$normalized] = ($bucket[$normalized] ?? 0) + 1;
    }
}
