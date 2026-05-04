<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Narrative Extraction Rules (DSL)
    |--------------------------------------------------------------------------
    |
    | These rules define how raw simulation metrics are converted into
    | "Narrative Tokens" (semantic situation markers).
    |
    */
    'rules' => [
        'CONFLICT_LEVEL_CRITICAL' => [
            ['key' => 'metrics.social.status.conflict_index', 'op' => '>', 'value' => 0.8]
        ],
        'TECHNOLOGICAL_GOLDEN_AGE' => [
            ['key' => 'metrics.innovation.tech_rate', 'op' => '>', 'value' => 0.85]
        ],
        'ENVIRONMENTAL_COLLAPSE_IMMINENT' => [
            ['key' => 'metrics.environment.stability', 'op' => '<', 'value' => 0.15]
        ],
        'ECONOMIC_PROSPERITY' => [
            ['key' => 'metrics.economy.growth_rate', 'op' => '>', 'value' => 0.05],
            ['key' => 'metrics.economy.inequality', 'op' => '<', 'value' => 0.4]
        ],
        'RELIGIOUS_FERVOR_RISING' => [
            ['key' => 'metrics.social.religion_influence', 'op' => '>', 'value' => 0.7]
        ],
        'POPULATION_EXPLOSION' => [
            ['key' => 'metrics.demographics.growth_rate', 'op' => '>', 'value' => 0.03]
        ],
        'STAGNANT_CIVILIZATION' => [
            ['key' => 'metrics.innovation.tech_rate', 'op' => '<', 'value' => 0.1],
            ['key' => 'metrics.economy.growth_rate', 'op' => '<', 'value' => 0.01]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Narrative Engine Settings
    |--------------------------------------------------------------------------
    */
    'engine' => [
        'min_interest_threshold' => 0.2, // Min change in metrics to trigger pulse
        'max_history_tokens' => 5,      // Number of recent tokens to keep in context
    ],
];
