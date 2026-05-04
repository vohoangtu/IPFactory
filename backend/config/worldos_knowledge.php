<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Knowledge graph (Doc §9) — nodes from Ideas, stub edges
    |--------------------------------------------------------------------------
    */
    'knowledge_graph' => [
        'interval' => (int) env('WORLDOS_KNOWLEDGE_GRAPH_INTERVAL', 10),
        'max_nodes' => (int) env('WORLDOS_KNOWLEDGE_GRAPH_MAX_NODES', 500),
        'max_edges' => (int) env('WORLDOS_KNOWLEDGE_GRAPH_MAX_EDGES', 200),
        // relation_type: derived_from (A derived from B), prerequisite (A is prerequisite for B). Key = idea info_type, value = list of source types.
        'derived_from_types' => [
            'science' => ['meme', 'propaganda'],
            'religion' => ['rumor', 'meme'],
            'propaganda' => ['rumor'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WorldOS Data Graph (Phase 5 Track B, doc §15)
    |--------------------------------------------------------------------------
    | When enabled and uri is set, sync WorldEvent to Neo4j (Event node, INVOLVES Actor).
    */
    'graph' => [
        'enabled' => env('WORLDOS_GRAPH_ENABLED', false),
        'uri' => env('WORLDOS_GRAPH_URI', 'bolt://localhost:7687'),
        'username' => env('WORLDOS_GRAPH_USERNAME'),
        'password' => env('WORLDOS_GRAPH_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Causality Graph (doc §4, §12) — event chain update when events are published
    |--------------------------------------------------------------------------
    | null: no-op. redis: store cause→effect chain per universe in Redis.
    */
    'causality' => [
        'driver' => env('WORLDOS_CAUSALITY_DRIVER', 'null'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Civilization Memory: aggregate key events per universe (Phase E)
    |--------------------------------------------------------------------------
    | max_events: cap on key_events returned; max_chronicles: cap for context.
    */
    'civilization_memory' => [
        'max_events' => (int) env('WORLDOS_CIV_MEMORY_MAX_EVENTS', 50),
        'max_chronicles' => (int) env('WORLDOS_CIV_MEMORY_MAX_CHRONICLES', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Civilization Discovery (Doc §36) — fitness evaluation interval
    |--------------------------------------------------------------------------
    */
    'civilization_discovery' => [
        'fitness_interval' => (int) env('WORLDOS_CIVILIZATION_DISCOVERY_FITNESS_INTERVAL', 10),
        'ga_top_k' => (int) env('WORLDOS_CIVILIZATION_DISCOVERY_GA_TOP_K', 2),
        'ga_universe_ids' => array_filter(array_map('intval', explode(',', env('WORLDOS_CIVILIZATION_DISCOVERY_GA_UNIVERSE_IDS', '')))),
        'ga_crossover_enabled' => (bool) env('WORLDOS_CIVILIZATION_DISCOVERY_GA_CROSSOVER_ENABLED', false),
        'ga_mutate_rate' => (float) env('WORLDOS_CIVILIZATION_DISCOVERY_GA_MUTATE_RATE', 0.05),
    ],

];
