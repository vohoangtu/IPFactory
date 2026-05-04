<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Social graph — trust, loyalty, rivalry (Doc §22)
    |--------------------------------------------------------------------------
    */
    'social_graph' => [
        'max_trust_edges' => (int) env('WORLDOS_SOCIAL_GRAPH_MAX_TRUST', 100),
        'max_loyalty_edges' => (int) env('WORLDOS_SOCIAL_GRAPH_MAX_LOYALTY', 100),
        'max_rivalry_edges' => (int) env('WORLDOS_SOCIAL_GRAPH_MAX_RIVALRY', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Macro agents (Deep Sim Phase B) — spawn limits and war threshold
    |--------------------------------------------------------------------------
    */
    'macro_agents' => [
        'max_per_zone' => (int) env('WORLDOS_MACRO_AGENTS_MAX_PER_ZONE', 3),
        'max_total' => (int) env('WORLDOS_MACRO_AGENTS_MAX_TOTAL', 20),
        'war_pressure_threshold' => (float) env('WORLDOS_MACRO_AGENTS_WAR_PRESSURE_THRESHOLD', 0.5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Actor Decision (Phase 2) — action types and max key actors per pulse
    |--------------------------------------------------------------------------
    */
    'actor_decision' => [
        'action_types' => ['write', 'teach', 'explore', 'war', 'meditate', 'create_religion', 'build', 'govern', 'trade', 'rest'],
        'max_actors_per_pulse' => (int) env('WORLDOS_ACTOR_DECISION_MAX_ACTORS_PER_PULSE', 50),
        'influence_threshold' => (float) env('WORLDOS_ACTOR_DECISION_INFLUENCE_THRESHOLD', 0.1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Idea diffusion (Phase 4) — followers threshold for school, growth
    |--------------------------------------------------------------------------
    */
    'idea_diffusion' => [
        'followers_threshold_for_school' => (int) env('WORLDOS_IDEA_FOLLOWERS_THRESHOLD', 10),
        'influence_growth_per_tick' => (float) env('WORLDOS_IDEA_INFLUENCE_GROWTH', 0.01),
        'run_on_pulse' => (bool) env('WORLDOS_PULSE_RUN_IDEA_DIFFUSION', false),
        // Doc §8: artifact_type → info_type (rumor|propaganda|science|religion|meme)
        'info_type_map' => [
            'prophecy' => 'religion',
            'invention' => 'science',
            'doctrine' => 'propaganda',
            'myth' => 'rumor',
            'meme' => 'meme',
        ],
        // Doc §8: institutional amplification (church→religion, state→propaganda, academy→science)
        'institutional_amplification' => [
            'religion' => (float) env('WORLDOS_IDEA_AMP_RELIGION', 1.2),
            'propaganda' => (float) env('WORLDOS_IDEA_AMP_PROPAGANDA', 1.15),
            'science' => (float) env('WORLDOS_IDEA_AMP_SCIENCE', 1.25),
            'rumor' => 1.0,
            'meme' => 1.05,
        ],
    ],

];
