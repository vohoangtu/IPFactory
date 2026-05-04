<?php

namespace App\Modules\Simulation\Services\Cosmology;

use App\Modules\World\Models\Universe;

class AxiomaticUniverseCreator
{
    /**
     * Common Axiom Templates for Project Eons.
     */
    public const TEMPLATES = [
        'realism' => [
            'gravity' => 1.0,           // Standard Earth gravity
            'energy_efficiency' => 0.4, // Standard metabolic conversion
            'mystic_constant' => 0.0,   // No magic/mana
            'material_abundance' => 0.5,
            'entropy_decay_rate' => 0.05,
        ],
        'wuxia' => [
            'gravity' => 0.8,           // Slightly lower for high jumps/martial arts
            'energy_efficiency' => 0.6,
            'mystic_constant' => 0.5,   // Medium Ki/Internal Energy
            'material_abundance' => 0.4,
            'entropy_decay_rate' => 0.03,
            'has_martial_arts' => true,
        ],
        'xuanhuan' => [
            'gravity' => 1.2,           // Heavy worlds
            'energy_efficiency' => 0.8,
            'mystic_constant' => 1.0,   // High Spiritual Qi
            'material_abundance' => 0.3,
            'entropy_decay_rate' => 0.01,
            'has_linh_ki' => true,
            'has_dao_laws' => true,
        ],
        'apocalyptic' => [
            'gravity' => 1.0,
            'energy_efficiency' => 0.2, // Scarce metabolism
            'mystic_constant' => 0.1,
            'material_abundance' => 0.1, // Depleted resources
            'entropy_decay_rate' => 0.15, // Rapid collapse
            'resource_scarcity' => true,
        ]
    ];

    /**
     * Apply a set of axioms to a universe based on a template.
     */
    public function initialize(Universe $universe, string $templateName = 'realism', array $overrides = []): Universe
    {
        $template = self::TEMPLATES[$templateName] ?? self::TEMPLATES['realism'];
        $axioms = array_merge($template, $overrides);

        $universe->axioms = $axioms;
        
        // Also sync some axioms to world if necessary (Legacy support)
        if ($universe->world) {
            $world = $universe->world;
            $worldAxiom = $world->axiom ?? [];
            foreach (['has_martial_arts', 'has_linh_ki', 'has_magic'] as $key) {
                if (isset($axioms[$key])) {
                    $worldAxiom[$key] = $axioms[$key];
                }
            }
            $world->axiom = $worldAxiom;
            $world->save();
        }

        $universe->save();
        return $universe;
    }

    /**
     * Generate a random set of unstable axioms for specialized scenarios.
     */
    public function generateUnstable(Universe $universe, int $seed): Universe
    {
        srand($seed);
        $axioms = [
            'gravity' => rand(50, 200) / 100.0,
            'energy_efficiency' => rand(10, 90) / 100.0,
            'mystic_constant' => rand(0, 100) / 100.0,
            'material_abundance' => rand(5, 100) / 100.0,
            'entropy_decay_rate' => rand(1, 20) / 100.0,
        ];

        $universe->axioms = $axioms;
        $universe->save();
        return $universe;
    }
}

