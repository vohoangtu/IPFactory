<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

/**
 * Phase 36: Universe Genome Presets.
 * Defines and applies preset configurations to the universe kernel genome.
 */
class UniverseGenomeService
{
    public const PRESETS = [
        'Hyper-Evolution' => [
            'diffusion_rate' => 0.8,
            'mutation_rate' => 0.15,
            'cohesion_bonus' => 1.2,
            'entropy_coefficient' => 0.4,
            'cognitive_bias' => 1.5
        ],
        'Entropy Lord' => [
            'diffusion_rate' => 0.2,
            'mutation_rate' => 0.4,
            'cohesion_bonus' => 0.5,
            'entropy_coefficient' => 2.5,
            'cognitive_bias' => 1.0
        ],
        'Eternal Silence' => [
            'diffusion_rate' => 0.01,
            'mutation_rate' => 0.01,
            'cohesion_bonus' => 2.0,
            'entropy_coefficient' => 0.1,
            'cognitive_bias' => 0.5
        ],
        'Technocracy' => [
            'diffusion_rate' => 0.6,
            'mutation_rate' => 0.05,
            'cohesion_bonus' => 1.5,
            'entropy_coefficient' => 0.3,
            'cognitive_bias' => 1.8
        ]
    ];

    /**
     * Apply a preset to the universe.
     * Note: In Level 9, this sets the 'target_genome' for smooth transition.
     */
    public function applyPreset(Universe $universe, string $presetName): bool
    {
        if (!isset(self::PRESETS[$presetName])) {
            Log::error("GENOME: Preset '{$presetName}' not found.");
            return false;
        }

        $presetData = self::PRESETS[$presetName];
        $stateVector = $universe->state_vector ?? [];
        
        // Phase 37: Set target_genome instead of overriding current genome directly
        $stateVector['target_genome'] = $presetData;
        $universe->state_vector = $stateVector;
        
        $universe->save();

        Log::info("GENOME: Target Genome set to '{$presetName}' for Universe #{$universe->id}.");
        return true;
    }

    /**
     * Get available presets.
     */
    public function getAvailablePresets(): array
    {
        return array_keys(self::PRESETS);
    }
}

