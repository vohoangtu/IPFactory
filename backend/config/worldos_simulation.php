<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Simulation Engine (Rust gRPC)
    |--------------------------------------------------------------------------
    */
    'simulation_engine_grpc_url' => env('SIMULATION_ENGINE_GRPC_URL', 'http://engine:50052'),

    /*
    |--------------------------------------------------------------------------
    | Simulation Tick Driver (Phase 5)
    |--------------------------------------------------------------------------
    | @deprecated — This toggle exists for backward compatibility only.
    |               Default is 'rust_only'. The 'laravel_kernel' option routes
    |               through the deprecated SimulationKernel and will be removed
    |               in a future release. Do not change this setting.
    |
    | laravel_kernel: tick from Rust, then optionally run Laravel SimulationKernel
    |                 (see simulation_kernel_post_tick) and overwrite snapshot.
    | rust_only:     tick entirely from Rust; Laravel only syncs state, saves
    |                 snapshot, fires events, runs listeners (AEE, fork, narrative).
    */
    'simulation_tick_driver' => env('WORLDOS_SIMULATION_TICK_DRIVER', 'rust_only'),

    /*
    |--------------------------------------------------------------------------
    | Simulation Kernel (Laravel-side post-tick)
    |--------------------------------------------------------------------------
    | @deprecated — Will be removed alongside SimulationKernel and simulation_tick_driver.
    | When true and simulation_tick_driver is laravel_kernel, after Rust engine
    | saves snapshot, run SimulationKernel and overwrite snapshot.
    */
    'simulation_kernel_post_tick' => env('SIMULATION_KERNEL_POST_TICK', false),

    /*
    |--------------------------------------------------------------------------
    | Rust authoritative simulation state (RUST_LARAVEL_SIMULATION_CONTRACT)
    |--------------------------------------------------------------------------
    | When true, Laravel MUST NOT overwrite civilization/economy/market/politics/war:
    | pipeline stages skip writing if state_vector already has the corresponding key.
    */
    'simulation' => [
        'rust_authoritative' => (bool) env('WORLDOS_SIMULATION_RUST_AUTHORITATIVE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chaos & Stability Control (Doc §32)
    |--------------------------------------------------------------------------
    */
    'chaos' => [
        'dampening_stability_factor' => (float) env('WORLDOS_CHAOS_DAMPENING_FACTOR', 0.6),
        'throttle_multiplier' => (float) env('WORLDOS_CHAOS_THROTTLE_MULTIPLIER', 0.5),
        'quarantine_instability_threshold' => (float) env('WORLDOS_CHAOS_QUARANTINE_THRESHOLD', 0.8),
        'quarantine_scale' => (float) env('WORLDOS_CHAOS_QUARANTINE_SCALE', 0.2),
    ],

    'emergence' => [
        'confidence_threshold' => (float) env('WORLDOS_EMERGENCE_CONFIDENCE_THRESHOLD', 0.7),
        'scale' => (float) env('WORLDOS_EMERGENCE_SCALE', 0.02),
        'max_probability' => (float) env('WORLDOS_EMERGENCE_MAX_PROBABILITY', 0.02),
        'optimal_entropy_world_will' => (float) env('WORLDOS_EMERGENCE_OPTIMAL_ENTROPY_WORLD_WILL', 0.4),
        'optimal_entropy_outer_god' => (float) env('WORLDOS_EMERGENCE_OPTIMAL_ENTROPY_OUTER_GOD', 0.85),
        'ticks_per_year' => (int) env('WORLDOS_EMERGENCE_TICKS_PER_YEAR', 12),
        'min_ticks_between_entities' => (int) env('WORLDOS_EMERGENCE_MIN_TICKS_BETWEEN', 200),
        'max_global_entities' => (int) env('WORLDOS_EMERGENCE_MAX_GLOBAL_ENTITIES', 5),
        'complexity_population_cap' => (int) env('WORLDOS_EMERGENCE_COMPLEXITY_POPULATION_CAP', 1_000_000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reality Calibration (Doc §33) — benchmarks + suggest, no auto-apply
    |--------------------------------------------------------------------------
    */
    'calibration' => [
        'auto_run' => (bool) env('WORLDOS_CALIBRATION_AUTO_RUN', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Observability (Doc §31) — Jaeger tracing optional
    |--------------------------------------------------------------------------
    | When tracing_enabled is true, simulation steps can emit spans (future).
    */
    'observability' => [
        'tracing_enabled' => (bool) env('WORLDOS_OBSERVABILITY_TRACING_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rule Engine / DSL (Doc WorldOS_DSL_Spec) — Rule VM in Rust
    |--------------------------------------------------------------------------
    | When enabled, after each saved snapshot the Rule VM is called with state;
    | outputs (events, adjust_stability, adjust_entropy) are applied.
    | rules_dsl: optional inline DSL string; if null, pass from caller or leave empty.
    | rules_path: optional path to .dsl file (e.g. engine/worldos-rules/rules/civilization.dsl).
    */
    'rule_engine' => [
        'enabled' => (bool) env('WORLDOS_RULE_ENGINE_ENABLED', false),
        'rules_dsl' => env('WORLDOS_RULE_ENGINE_DSL'), // optional inline DSL
        'rules_path' => env('WORLDOS_RULE_ENGINE_RULES_PATH'), // optional path to .dsl file
        'use_deployed_from_table' => (bool) env('WORLDOS_RULE_ENGINE_USE_DEPLOYED_FROM_TABLE', false), // Doc §30: append latest deployed rule from rule_proposals
    ],
    /*
    |--------------------------------------------------------------------------
    | Autopoiesis: Self-improving simulation (Doc §30, Phase 74)
    |--------------------------------------------------------------------------
    | Tự động hóa việc đột biến quy luật DSL dựa trên áp lực thực tại (Entropy/Stability).
    | enabled: Cho phép hệ thống tự can thiệp vào mã nguồn quy luật.
    | tick_interval: Tần suất kiểm tra đột biến (mặc định 100 ticks).
    | entropy_threshold: Ngưỡng entropy bắt đầu kích hoạt cơ chế ổn định (0.70).
    */
    'autopoiesis' => [
        'enabled' => (bool) env('WORLDOS_AUTOPOIESIS_ENABLED', true),
        'tick_interval' => (int) env('WORLDOS_AUTOPOIESIS_TICK_INTERVAL', 100),
        'entropy_threshold' => (float) env('WORLDOS_AUTOPOIESIS_ENTROPY_THRESHOLD', 0.70),
    ],

    'self_improving' => [
        'enabled' => (bool) env('WORLDOS_SELF_IMPROVING_ENABLED', false),
        'candidate_rules' => [
            'simulation_tick' => env('WORLDOS_SELF_IMPROVING_CANDIDATE_DSL', 'rule entropy > 0.8 => emit_event entropy_critical'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Entropy floor (drift sàn tối thiểu)
    |--------------------------------------------------------------------------
    | Khi tick > 0, entropy từ engine/stub không được dưới giá trị này (tránh 0 thuần).
    | Giảm giá trị để entropy có thể gần 0 hơn (vd. 0.001 thay vì 0.003).
    */
    'entropy_floor' => (float) env('WORLDOS_ENTROPY_FLOOR', 0.001),

    /*
    |--------------------------------------------------------------------------
    | Tick Pipeline (Simulation Runtime Architecture)
    |--------------------------------------------------------------------------
    | Stage key => ['interval' => N]. Stage runs when tick % N === 0. Order below = run order.
    | actor/culture/civilization/ecology/meta: typically 1 (every tick). economy/politics/war: slower.
    */
    'tick_pipeline' => [
        'actor' => ['interval' => (int) env('WORLDOS_TICK_PIPELINE_ACTOR', 1)],
        'culture' => ['interval' => (int) env('WORLDOS_TICK_PIPELINE_CULTURE', 1)],
        'civilization' => ['interval' => (int) env('WORLDOS_TICK_PIPELINE_CIVILIZATION', 1)],
        'economy' => ['interval' => (int) env('WORLDOS_TICK_PIPELINE_ECONOMY', 10)],
        'politics' => ['interval' => (int) env('WORLDOS_TICK_PIPELINE_POLITICS', 20)],
        'war' => ['interval' => (int) env('WORLDOS_TICK_PIPELINE_WAR', 50)],
        'ecology' => ['interval' => (int) env('WORLDOS_TICK_PIPELINE_ECOLOGY', 1)],
        'meta' => ['interval' => (int) env('WORLDOS_TICK_PIPELINE_META', 1)],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Bus Backend (Phase 5 Track A)
    |--------------------------------------------------------------------------
    | database: persist to world_events table, dispatch Laravel event.
    | redis_stream: XADD to Redis Stream (world_events) then persist + dispatch.
    */
    'event_bus' => [
        'driver' => env('WORLDOS_EVENT_BUS_DRIVER', 'database'),
        'stream_key' => env('WORLDOS_EVENT_BUS_STREAM_KEY', 'world_events'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Stream (Kafka) — Phase 1, doc §38
    |--------------------------------------------------------------------------
    | Publish simulation events to Kafka for history log, AI/Narrative layer.
    | rest_proxy_url: Confluent Kafka REST Proxy or Redpanda REST (e.g. http://localhost:8082).
    | Message format: JSON with universe_id, tick, type (simulation_advanced|rule_fired), event_name?, payload, occurred_at (ISO8601).
    */
    'event_stream' => [
        'kafka_enabled' => (bool) env('WORLDOS_EVENT_STREAM_KAFKA_ENABLED', false),
        'rest_proxy_url' => rtrim(env('WORLDOS_EVENT_STREAM_REST_PROXY_URL', 'http://localhost:8082'), '/'),
        'topic_simulation_advanced' => env('WORLDOS_EVENT_STREAM_TOPIC_ADVANCED', 'worldos.simulation.advanced'),
        'topic_events' => env('WORLDOS_EVENT_STREAM_TOPIC_EVENTS', 'worldos.simulation.events'),
    ],

    /*
    |--------------------------------------------------------------------------
    | State cache (optional) — Phase 2 §2.3
    |--------------------------------------------------------------------------
    | When driver=redis, state_vector + tick are written to Redis after each sync (TTL).
    | EngineDriver may prefer cached state when preparing advance input. Reduces DB read.
    */
    'state_cache' => [
        'driver' => env('WORLDOS_STATE_CACHE_DRIVER', 'null'),
        'ttl_seconds' => (int) env('WORLDOS_STATE_CACHE_TTL_SECONDS', 300),
        'key_prefix' => env('WORLDOS_STATE_CACHE_KEY_PREFIX', 'worldos:'),
    ],

    'urban_stress_agriculture' => [
        'enabled' => (bool) env('WORLDOS_URBAN_STRESS_AGRICULTURE_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Snapshot archive (Doc §10, RÀ_SOÁT_TMP mục 7) — cold storage S3/MinIO
    |--------------------------------------------------------------------------
    */
    'snapshot' => [
        'archive_driver' => env('WORLDOS_SNAPSHOT_ARCHIVE_DRIVER', 'null'), // null | s3
        'archive' => [
            'disk' => env('WORLDOS_SNAPSHOT_ARCHIVE_DISK', 's3'),
            'prefix' => env('WORLDOS_SNAPSHOT_ARCHIVE_PREFIX', 'worldos/snapshots'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Time-Scale Engine (tick factors) — Simulation Kernel (Tier 3)
    |--------------------------------------------------------------------------
    | Tick rate per kernel engine: engine runs when tick % factor === 0.
    | physics=1 (every tick), ecology=10, evolution=100, climate=500, etc.
    | Each SimulationEngine implements tickRate() reading from this config.
    */
    'time_scale_factors' => [
        'potential_field' => (int) env('WORLDOS_TIME_SCALE_POTENTIAL_FIELD', 1),
        'zone_conflict' => (int) env('WORLDOS_TIME_SCALE_ZONE_CONFLICT', 1),
        'cosmic_pressure' => (int) env('WORLDOS_TIME_SCALE_COSMIC_PRESSURE', 1),
        'structural_decay' => (int) env('WORLDOS_TIME_SCALE_STRUCTURAL_DECAY', 5),
        'law_evolution' => (int) env('WORLDOS_TIME_SCALE_LAW_EVOLUTION', 20),
        'cultural_drift' => (int) env('WORLDOS_TIME_SCALE_CULTURAL_DRIFT', 3),
        'adaptive_topology' => (int) env('WORLDOS_TIME_SCALE_ADAPTIVE_TOPOLOGY', 50),
        'multiverse_osmosis' => (int) env('WORLDOS_TIME_SCALE_MULTIVERSE_OSMOSIS', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Multiverse Scheduler: tick budget and priority (Phase B)
    |--------------------------------------------------------------------------
    | tick_budget: max universes to tick per world per cycle (0 = no limit).
    | priority_weights: novelty, complexity, civilization, entropy (sum to 1).
    */
    'scheduler' => [
        'tick_budget' => (int) env('WORLDOS_SCHEDULER_TICK_BUDGET', 10),
        'priority_weights' => [
            'novelty' => (float) env('WORLDOS_SCHEDULER_WEIGHT_NOVELTY', 0.25),
            'complexity' => (float) env('WORLDOS_SCHEDULER_WEIGHT_COMPLEXITY', 0.30),
            'civilization' => (float) env('WORLDOS_SCHEDULER_WEIGHT_CIVILIZATION', 0.25),
            'entropy' => (float) env('WORLDOS_SCHEDULER_WEIGHT_ENTROPY', 0.20),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Engine Registry (Phase 3 auto-register) — FQCN list for SimulationEngine
    |--------------------------------------------------------------------------
    | Tagged as 'simulation_engine'; EngineRegistry resolves via tagged().
    */
    'engine_registry' => [
        'engines' => [
            \App\Modules\World\Services\GeographyEngine::class,
            \App\Modules\Simulation\Core\Engines\Physics\PotentialFieldEngine::class,
            \App\Modules\Simulation\Core\Engines\Physics\StructuralDecayEngine::class,
            \App\Modules\Simulation\Core\Engines\Physics\MetabolicEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\LawEvolutionEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\CausalityEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\MultiverseOsmosisEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\CausalBridgeEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\NarrativeInterpretationEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\NarrativePropagationEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\NarrativeConflictEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\MemoryReflectionEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\CausalHistoryEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\OmegaConvergenceEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\PostApotheosisEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\ResonanceBleedingEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\DynamicLawEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\DeepTimeMemoryEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\HigherDimensionalEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\InfiniteRecursionEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\IdealismEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\SingularityEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\InformationDensityEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\IdeologyEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\CulturalInfluenceEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\MetaAttractorEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\InformationPropagationEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\PowerStructureEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\MythogenesisEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\MeaningEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\KnowledgeEvolutionEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\ThermodynamicPhaseEngine::class,
            \App\Modules\Simulation\Core\Engines\Meta\SingularityStabilityEngine::class,
            \App\Modules\Simulation\Core\Engines\Social\AgricultureEngine::class,
            \App\Modules\Simulation\Core\Engines\Social\PopulationEngine::class,
            \App\Modules\Simulation\Core\Engines\Social\DiseaseEngine::class,
            \App\Modules\Simulation\Core\Engines\Biological\AutopoieticEvolutionEngine::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pulse: run Ideology & Great Person each pulse (Phase K)
    |--------------------------------------------------------------------------
    */
    'pulse' => [
        'run_ideology' => (bool) env('WORLDOS_PULSE_RUN_IDEOLOGY', true),
        'run_great_person' => (bool) env('WORLDOS_PULSE_RUN_GREAT_PERSON', true),
        'run_great_person_legacy' => (bool) env('WORLDOS_PULSE_RUN_GREAT_PERSON_LEGACY', true),
        'run_actor_decision' => (bool) env('WORLDOS_PULSE_RUN_ACTOR_DECISION', false),
    ],

];
