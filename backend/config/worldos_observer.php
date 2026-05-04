<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Civilization Interpreter (Doc §4.6, §18) — narrative from snapshot
    |--------------------------------------------------------------------------
    | template: optional string with {{tick}}, {{entropy}}, {{war_stage}}, {{population}}, {{religion}}.
    */
    'narrative_interpreter' => [
        'template' => env('WORLDOS_NARRATIVE_INTERPRETER_TEMPLATE'), // optional
    ],

    'narrative_llm_url' => env('NARRATIVE_LLM_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Narrative 4-tier: Scheduler + engine intervals (event, era, civilization, mythology, religion, prophecy, legend)
    |--------------------------------------------------------------------------
    */
    'narrative' => [
        'kafka_enabled' => (bool) env('WORLDOS_NARRATIVE_KAFKA_ENABLED', false),
        'era_interval' => (int) env('WORLDOS_NARRATIVE_ERA_INTERVAL', 200),
        'civilization_on_collapse' => true,
        'mythology_on_events' => true,
        'mythology_interval' => (int) env('WORLDOS_NARRATIVE_MYTHOLOGY_INTERVAL', 50),
        'religion_interval' => (int) env('WORLDOS_NARRATIVE_RELIGION_INTERVAL', 200),
        'prophecy_interval' => (int) env('WORLDOS_NARRATIVE_PROPHECY_INTERVAL', 500),
        'legend_interval' => (int) env('WORLDOS_NARRATIVE_LEGEND_INTERVAL', 100),
        'religion_impact_threshold' => (float) env('WORLDOS_NARRATIVE_RELIGION_IMPACT_THRESHOLD', 0.6),
        'prophecy_horizon_ticks' => (int) env('WORLDOS_NARRATIVE_PROPHECY_HORIZON_TICKS', 100),
        'prompt_templates' => [
            'era' => "Civilizations rising: {civilizations}\nMajor wars: {wars}\nAnomalies: {anomalies}\nClimate events: {climate}\n\nSTRICT FACTS:\n{facts}\n\nWrite a short historical paragraph without contradicting the facts.",
            'civilization' => "Civilization: {name}. Origin tick: {origin_tick}, Collapse tick: {collapse_tick}.\n\nSTRICT FACTS:\n{facts}\n\nWrite origin_story / golden_age_story / collapse_story without contradicting the facts.",
            'myth' => "Source events: {events}\n\nSTRICT FACTS:\n{facts}\n\nTurn these into a short myth/legend without contradicting the facts.",
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Narrative Engine v2 (fact-first, perspective, memory graph, historian)
    |--------------------------------------------------------------------------
    | When enabled, pulse records WorldEvent + Historical Fact, then Chronicle
    | uses fact + perspective; Memory Graph links event→chronicle. Historian
    | is on-demand only (no auto-run in pulse). All narrative v2 logic except
    | LLM calls is deterministic.
    */
    'narrative_v2' => [
        'enable_world_event' => (bool) env('WORLDOS_NARRATIVE_V2_WORLD_EVENT', true),
        'enable_fact_first_chronicle' => (bool) env('WORLDOS_NARRATIVE_V2_FACT_FIRST', true),
        'enable_perspective_layer' => (bool) env('WORLDOS_NARRATIVE_V2_PERSPECTIVE', true),
        'enable_memory_graph' => (bool) env('WORLDOS_NARRATIVE_V2_MEMORY_GRAPH', true),
        'enable_historian_agent' => (bool) env('WORLDOS_NARRATIVE_V2_HISTORIAN_AGENT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Narrative Extraction: timeline → story/lore (Phase D)
    |--------------------------------------------------------------------------
    | default_limit: max universes to extract when using extractBestFromWorld/Saga.
    | chronicle_type: type stored in Chronicle when extracting (lore / story).
    */
    'narrative_extraction' => [
        'default_limit' => (int) env('WORLDOS_NARRATIVE_EXTRACTION_LIMIT', 5),
        'chronicle_type' => env('WORLDOS_NARRATIVE_EXTRACTION_TYPE', 'lore'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mythology Generator: chronicle type for generated myth (Phase F)
    |--------------------------------------------------------------------------
    */
    'mythology_generator' => [
        'chronicle_type' => env('WORLDOS_MYTHOLOGY_CHRONICLE_TYPE', 'myth'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeline Selection: narrative interest scoring (Phase C)
    |--------------------------------------------------------------------------
    | default_limit: max timelines returned by selectBest (0 = no limit).
    | narrative_weights: novelty, complexity, divergence, depth, tension (sum ~1).
    |   tension = entropy in "interesting" range (0.3–0.7) for story potential.
    */
    'timeline_selection' => [
        'default_limit' => (int) env('WORLDOS_TIMELINE_SELECTION_LIMIT', 10),
        'narrative_weights' => [
            'novelty' => (float) env('WORLDOS_TSE_WEIGHT_NOVELTY', 0.25),
            'complexity' => (float) env('WORLDOS_TSE_WEIGHT_COMPLEXITY', 0.25),
            'divergence' => (float) env('WORLDOS_TSE_WEIGHT_DIVERGENCE', 0.20),
            'depth' => (float) env('WORLDOS_TSE_WEIGHT_DEPTH', 0.15),
            'tension' => (float) env('WORLDOS_TSE_WEIGHT_TENSION', 0.15),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cosmic Phase (dominant axis + hysteresis)
    |--------------------------------------------------------------------------
    | Phase = argmax(faith, chaos, order, tech). Change only when
    | new_dominant_score > previous_dominant_score + hysteresis.
    */
    'cosmic_phase' => [
        'hysteresis' => (float) env('WORLDOS_COSMIC_PHASE_HYSTERESIS', 0.15),
        // Optional: per-phase modifiers for narrative/engine (e.g. faith => ['faith_generation_multiplier' => 1.5])
        'modifiers' => [
            'faith' => [],
            'chaos' => [],
            'order' => [],
            'tech' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Market (prices, volatility, events)
    |--------------------------------------------------------------------------
    | economy.market in state_vector; MARKET_CRASH / ECONOMIC_BOOM events.
    */
    'market' => [
        'price_base_food' => (float) env('WORLDOS_MARKET_PRICE_BASE_FOOD', 1.0),
        'price_min_food' => (float) env('WORLDOS_MARKET_PRICE_MIN_FOOD', 0.2),
        'price_max_food' => (float) env('WORLDOS_MARKET_PRICE_MAX_FOOD', 5.0),
        'crash_price_threshold' => (float) env('WORLDOS_MARKET_CRASH_PRICE_THRESHOLD', 0.4),
        'boom_surplus_threshold' => (float) env('WORLDOS_MARKET_BOOM_SURPLUS_THRESHOLD', 50.0),
        'emit_trade_route_event' => (bool) env('WORLDOS_MARKET_EMIT_TRADE_ROUTE_EVENT', true),
        // Energy price from cosmic_energy_pool scarcity (pool low → price high). Laravel meta layer only.
        'price_base_energy' => (float) env('WORLDOS_MARKET_PRICE_BASE_ENERGY', 1.0),
        'price_min_energy' => (float) env('WORLDOS_MARKET_PRICE_MIN_ENERGY', 0.3),
        'price_max_energy' => (float) env('WORLDOS_MARKET_PRICE_MAX_ENERGY', 4.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Geography / Resource capacity (Deep Sim Phase A)
    |--------------------------------------------------------------------------
    | Optional: zone_id => capacity (0.0–1.0). If not set, prepareEngineStateInput
    | uses deterministic formula: 0.3 + 0.2 * (zone_id % 3), clamped to [0,1].
    */
    'geography' => [
        'resource_capacity' => [], // e.g. [1 => 0.8, 2 => 0.5] to override per zone
    ],

    /*
    |--------------------------------------------------------------------------
    | Geological Engine (Tier 5)
    |--------------------------------------------------------------------------
    | Elevation, terrain type, mineral distribution per zone. Very slow (geology_tick 5000+).
    | Terrain feeds Climate (elevation) and future Civilization. Deterministic: seed + tick.
    */
    'geological' => [
        'tick_interval' => (int) env('WORLDOS_GEOLOGICAL_TICK_INTERVAL', 5000),
        'elevation_drift_rate' => (float) env('WORLDOS_GEOLOGICAL_ELEVATION_DRIFT', 0.002),
        'volcano_probability_per_zone' => (float) env('WORLDOS_GEOLOGICAL_VOLCANO_PROB', 0.02),
        'erosion_rate' => (float) env('WORLDOS_GEOLOGICAL_EROSION_RATE', 0.001),
    ],

    /*
    |--------------------------------------------------------------------------
    | Planetary Climate Engine (Tier 4)
    |--------------------------------------------------------------------------
    | Solar input, latitude zones, temperature/rainfall per zone, seasonal cycle.
    | Output feeds Phase Transition and biome. Runs slowly (e.g. every 500 ticks).
    */
    'planetary_climate' => [
        'tick_interval' => (int) env('WORLDOS_PLANETARY_CLIMATE_TICK_INTERVAL', 500),
        'seasonal_cycle_ticks' => (int) env('WORLDOS_PLANETARY_CLIMATE_SEASONAL_TICKS', 1000),
        'base_temperature' => (float) env('WORLDOS_PLANETARY_CLIMATE_BASE_TEMP', 0.5),
        'latitude_temperature_amplitude' => (float) env('WORLDOS_PLANETARY_CLIMATE_LAT_TEMP', 0.25),
        'seasonal_temperature_amplitude' => (float) env('WORLDOS_PLANETARY_CLIMATE_SEASON_TEMP', 0.1),
        'equator_rainfall' => (float) env('WORLDOS_PLANETARY_CLIMATE_EQUATOR_RAIN', 0.75),
        'pole_rainfall' => (float) env('WORLDOS_PLANETARY_CLIMATE_POLE_RAIN', 0.2),
        'ice_coverage_temp_threshold' => (float) env('WORLDOS_PLANETARY_CLIMATE_ICE_TEMP', 0.25),
    ],

    /*
    |--------------------------------------------------------------------------
    | Eschaton: material survivability (per ontology)
    |--------------------------------------------------------------------------
    | Chance (0..1) that a material instance survives Eschaton. Symbolic/mythic ideas survive more.
    */
    'eschaton_survivability' => [
        'symbolic' => (float) env('WORLDOS_ESCHATON_SURVIVABILITY_SYMBOLIC', 0.25),
        'institutional' => (float) env('WORLDOS_ESCHATON_SURVIVABILITY_INSTITUTIONAL', 0.15),
        'behavioral' => (float) env('WORLDOS_ESCHATON_SURVIVABILITY_BEHAVIORAL', 0.1),
        'physical' => (float) env('WORLDOS_ESCHATON_SURVIVABILITY_PHYSICAL', 0.05),
        'default' => (float) env('WORLDOS_ESCHATON_SURVIVABILITY_DEFAULT', 0.1),
    ],

    'potential_field_war_threshold' => (float) env('WORLDOS_POTENTIAL_FIELD_WAR_THRESHOLD', 0.85),

    /*
    |--------------------------------------------------------------------------
    | Artifact creation (Phase 3) — thresholds and action → artifact_type
    |--------------------------------------------------------------------------
    */
    'artifact' => [
        'creativity_threshold' => (float) env('WORLDOS_ARTIFACT_CREATIVITY_THRESHOLD', 0.4),
        'cognition_threshold' => (float) env('WORLDOS_ARTIFACT_COGNITION_THRESHOLD', 0.35),
        'create_probability' => (float) env('WORLDOS_ARTIFACT_CREATE_PROBABILITY', 0.25),
        'action_to_type' => [
            'write' => 'book',
            'create_religion' => 'religion',
            'build' => 'architecture',
        ],
    ],

];
