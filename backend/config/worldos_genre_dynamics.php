<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WorldOS Genre Dynamics Configuration
    |--------------------------------------------------------------------------
    |
    | Defines the physical properties of "Phase Basins" in the state space.
    | Space dimensions (0.0 - 1.0): spirituality, hardtech, entropy, energy_level
    |
    | Properties per Basin:
    | - center: The ideal coordinate of this Phase Basin.
    | - field_radius: Distance at which the gravitational pull is meaningful.
    | - drift_elasticity: Strength of the pull towards the center.
    | - mutation_probability: Base chance for emergent anomalies when in this basin.
    | - phase_thresholds: Conditions that must be met to "lock" into this basin heavily,
    |                     or collapse out of it.
    |
    */

    'basins' => [
        // ==========================
        // HISTORICAL & MARTIAL PATH
        // ==========================
        'historical' => [
            'center' => ['spirituality' => 0.2, 'hardtech' => 0.1, 'entropy' => 0.2, 'energy_level' => 0.1],
            'field_radius' => 0.15,
            'drift_elasticity' => 0.5,
            'mutation_probability' => 0.01,
        ],
        'military_history' => [
            'center' => ['spirituality' => 0.1, 'hardtech' => 0.3, 'entropy' => 0.6, 'energy_level' => 0.15],
            'field_radius' => 0.2,
            'drift_elasticity' => 0.6,
            'mutation_probability' => 0.05,
        ],
        'wuxia' => [
            'center' => ['spirituality' => 0.6, 'hardtech' => 0.1, 'entropy' => 0.4, 'energy_level' => 0.3],
            'field_radius' => 0.25,
            'drift_elasticity' => 0.7,
            'mutation_probability' => 0.03,
        ],
        'high_martial' => [
            'center' => ['spirituality' => 0.75, 'hardtech' => 0.1, 'entropy' => 0.5, 'energy_level' => 0.6],
            'field_radius' => 0.2,
            'drift_elasticity' => 0.6,
            'mutation_probability' => 0.04,
        ],
        'xianxia' => [
            'center' => ['spirituality' => 0.95, 'hardtech' => 0.05, 'entropy' => 0.6, 'energy_level' => 0.95],
            'field_radius' => 0.15, // Tight basin, hard to stay in
            'drift_elasticity' => 0.9,
            'mutation_probability' => 0.02,
        ],

        // ==========================
        // URBAN & MODERN PATH
        // ==========================
        'slice_of_life' => [
            'center' => ['spirituality' => 0.05, 'hardtech' => 0.4, 'entropy' => 0.1, 'energy_level' => 0.05],
            'field_radius' => 0.1,
            'drift_elasticity' => 0.8, // Tends to pull strongly towards stability
            'mutation_probability' => 0.005,
        ],
        'showbiz' => [
            'center' => ['spirituality' => 0.05, 'hardtech' => 0.5, 'entropy' => 0.3, 'energy_level' => 0.1],
            'field_radius' => 0.15,
            'drift_elasticity' => 0.4,
            'mutation_probability' => 0.05,
        ],
        'urban' => [
            'center' => ['spirituality' => 0.1, 'hardtech' => 0.6, 'entropy' => 0.4, 'energy_level' => 0.15],
            'field_radius' => 0.3, // Huge basin, modern society is very sticky
            'drift_elasticity' => 0.6,
            'mutation_probability' => 0.02,
        ],
        'urban_martial' => [
            'center' => ['spirituality' => 0.3, 'hardtech' => 0.6, 'entropy' => 0.5, 'energy_level' => 0.3],
            'field_radius' => 0.2,
            'drift_elasticity' => 0.5,
            'mutation_probability' => 0.06,
        ],
        'urban_esper' => [
            'center' => ['spirituality' => 0.4, 'hardtech' => 0.6, 'entropy' => 0.5, 'energy_level' => 0.4],
            'field_radius' => 0.2,
            'drift_elasticity' => 0.5,
            'mutation_probability' => 0.08, // High mutation rate (esper awakenings)
        ],
        'reiki_revival' => [
            'center' => ['spirituality' => 0.8, 'hardtech' => 0.4, 'entropy' => 0.9, 'energy_level' => 0.8],
            'field_radius' => 0.3,
            'drift_elasticity' => 0.7,
            'mutation_probability' => 0.15, // Extremely chaotic and highly mutative
            'phase_thresholds' => [
                'collapse_entropy' => 0.95,
            ]
        ],

        // ==========================
        // APOCALYPTIC & SCI-FI PATH
        // ==========================
        'apocalypse' => [
            'center' => ['spirituality' => 0.1, 'hardtech' => 0.3, 'entropy' => 0.95, 'energy_level' => 0.2],
            'field_radius' => 0.4, // Massive basin once society collapses
            'drift_elasticity' => 0.9, // Very hard to escape the apocalypse
            'mutation_probability' => 0.1, // High mutation (zombies/mutants)
        ],
        'cyberpunk' => [
            'center' => ['spirituality' => 0.05, 'hardtech' => 0.85, 'entropy' => 0.8, 'energy_level' => 0.4],
            'field_radius' => 0.2,
            'drift_elasticity' => 0.8,
            'mutation_probability' => 0.05,
        ],
        'sci_fi' => [
            'center' => ['spirituality' => 0.1, 'hardtech' => 0.95, 'entropy' => 0.5, 'energy_level' => 0.9],
            'field_radius' => 0.25,
            'drift_elasticity' => 0.6,
            'mutation_probability' => 0.03,
        ],
    ]
];
