<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Product key → engine/source names (for UI "Engine liên quan").
    | Keys match frontend personae sub: actors, factions, civilizations, supreme, integrity, materials, attractors.
    | See backend/docs/ENGINE_PRODUCTS.md.
    |--------------------------------------------------------------------------
    */
    'product_to_engines' => [
        'actors' => [
            'Intelligence',
            'GetUniverseActorsAction',
            'ActorBehaviorEngine',
            'ActorEvolutionService',
        ],
        'factions' => [
            'ReligionEngine',
            'GovernanceEngine',
            'CivilizationFormationEngine',
            'LawEvolutionEngine',
        ],
        'civilizations' => [
            'CivilizationFormationEngine',
            'ZoneConflictEngine',
            'GreatFilterEngine',
        ],
        'supreme' => [
            'AscensionEngine',
            'GreatPersonEngine',
        ],
        'integrity' => [
            'SupremeEntity.karma (cùng nguồn Thực thể Tối cao)',
        ],
        'materials' => [
            'ScenarioEngine',
            'Material DAG',
            'evolution pipeline',
        ],
        'attractors' => [
            'DynamicAttractorEngine',
            'CivilizationCollapseEngine',
            'snapshot active_attractors',
        ],
    ],
];
