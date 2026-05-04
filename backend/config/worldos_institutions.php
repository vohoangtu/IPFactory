<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ideology Evolution: aggregate ideology from institutions (Phase G)
    |--------------------------------------------------------------------------
    */
    'ideology_evolution' => [
        'store_in_state_vector' => (bool) env('WORLDOS_IDEOLOGY_STORE_IN_STATE', true),
        'conversion_base_rate' => (float) env('WORLDOS_IDEOLOGY_CONVERSION_BASE_RATE', 0.01),
    ],

    /*
    |--------------------------------------------------------------------------
    | Power Economy / Cosmic Energy Pool
    |--------------------------------------------------------------------------
    | Universe-level cosmic energy pool fed by cosmic_phase + energy_level and
    | active Supreme Entities. Stored in state_vector.cosmic_energy_pool.
    */
    'power_economy' => [
        'enabled' => (bool) env('WORLDOS_POWER_ECONOMY_ENABLED', false),
        'cosmic_pool_max' => (float) env('WORLDOS_POWER_ECONOMY_COSMIC_POOL_MAX', 100.0),
        'inflow_scale' => (float) env('WORLDOS_POWER_ECONOMY_INFLOW_SCALE', 0.1),
        'decay_per_tick' => (float) env('WORLDOS_POWER_ECONOMY_DECAY_PER_TICK', 0.001),
        'feed_zones' => (bool) env('WORLDOS_POWER_ECONOMY_FEED_ZONES', false),
        'feed_zones_ratio' => (float) env('WORLDOS_POWER_ECONOMY_FEED_ZONES_RATIO', 0.01),
        'feed_zones_cap_per_zone' => (float) env('WORLDOS_POWER_ECONOMY_FEED_ZONES_CAP_PER_ZONE', 2.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Economy — trade flow, hub_score (Doc §16)
    |--------------------------------------------------------------------------
    */
    'economy' => [
        'trade_route_capacity_factor' => (float) env('WORLDOS_ECONOMY_TRADE_ROUTE_CAPACITY', 0.5),
        'hub_connectivity_factor' => (float) env('WORLDOS_ECONOMY_HUB_CONNECTIVITY', 0.3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Inequality (Doc §7) — gini, surplus concentration
    |--------------------------------------------------------------------------
    */
    'inequality' => [
        'elite_population_share' => (float) env('WORLDOS_INEQUALITY_ELITE_SHARE', 0.1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Legitimacy & elite (Doc §17)
    |--------------------------------------------------------------------------
    */
    'legitimacy' => [
        'elite_overproduction_threshold' => (float) env('WORLDOS_LEGITIMACY_ELITE_OVERPRODUCTION', 0.15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Institution (Phase 5) — decay and behaviour
    |--------------------------------------------------------------------------
    */
    'institution' => [
        'decay_rate' => (float) env('WORLDOS_INSTITUTION_DECAY_RATE', 0.005),
        'run_decay_on_pulse' => (bool) env('WORLDOS_PULSE_RUN_INSTITUTION_DECAY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Institutions — evolution thresholds and spawning config
    |--------------------------------------------------------------------------
    */
    'institutions' => [
        'stability_threshold' => (float) env('WORLDOS_INSTITUTION_STABILITY_THRESHOLD', 0.4),
        'skip_probability' => (int) env('WORLDOS_INSTITUTION_SKIP_PROBABILITY', 3),
        'stress_threshold' => (float) env('WORLDOS_INSTITUTION_STRESS_THRESHOLD', 0.8),
        'org_capacity_threshold' => (int) env('WORLDOS_INSTITUTION_ORG_CAPACITY', 60),
        'max_actors' => (int) env('WORLDOS_INSTITUTION_MAX_ACTORS', 15),
    ],

];
