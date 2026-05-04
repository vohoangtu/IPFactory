<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Intelligence: Dramatis Personae (actors)
    |--------------------------------------------------------------------------
    | actor_minimum_population: Số actor sống tối thiểu mỗi universe. Mỗi pulse
    | nếu số alive < số này sẽ spawn thêm cho đủ. Đặt 0 = không tự spawn (không giới hạn).
    | Gợi ý: 0 (tắt), 3–5 (ít, narrative tập trung), 8–12 (nhiều, xã hội đông).
    | ticks_per_year: Số tick mô phỏng tương đương 1 năm trong world. VD: 1 = mỗi tick là 1 năm, 12 = mỗi tick ~1 tháng.
    | default_max_age_years: Tuổi thọ tối đa (năm) mặc định cho actor dạng "human". Actor chết khi age_years >= effective_max (effective_max phụ thuộc trait Longevity).
    | mortality_curve: age_ratio = age / life_expectancy; death_prob per tick theo từng khoảng (trẻ ít chết, già chết nhiều).
    */
    'intelligence' => [
        'actor_minimum_population' => (int) env('WORLDOS_ACTOR_MINIMUM_POPULATION', 5),
        'ticks_per_year' => (int) env('WORLDOS_TICKS_PER_YEAR', 1),
        'default_max_age_years' => (int) env('WORLDOS_DEFAULT_MAX_AGE_YEARS', 150),
        // Mortality curve: [age_ratio_max => death_prob_per_tick]. age_ratio = age / life_expectancy. String keys to avoid float→int deprecation.
        'mortality_curve' => [
            '0.6' => (float) env('WORLDOS_MORTALITY_DEATH_PROB_YOUNG', 0.001),
            '1.0' => (float) env('WORLDOS_MORTALITY_DEATH_PROB_MID', 0.01),
            'old' => (float) env('WORLDOS_MORTALITY_DEATH_PROB_OLD', 0.2), // age_ratio >= 1.0
        ],
        // Energy economy (Phase 2)
        'metabolism_base' => (float) env('WORLDOS_METABOLISM_BASE', 0.5),
        'energy_max_default' => (float) env('WORLDOS_ENERGY_MAX_DEFAULT', 200),
        'starvation_threshold' => (float) env('WORLDOS_STARVATION_THRESHOLD', 20),
        'gather_rate' => (float) env('WORLDOS_GATHER_RATE', 5),
        'resource_regen_rate' => (float) env('WORLDOS_RESOURCE_REGEN_RATE', 2),
        // Reproduction (Phase 2b)
        'reproduce_cost' => (float) env('WORLDOS_REPRODUCE_COST', 80),
        'reproduce_energy_ratio_child' => (float) env('WORLDOS_REPRODUCE_ENERGY_RATIO_CHILD', 0.3),
        'mutation_rate' => (float) env('WORLDOS_MUTATION_RATE', 0.05),
        // Ecological Collapse Engine (Tier 1)
        'ecological_collapse_tick_interval' => (int) env('WORLDOS_ECOLOGICAL_COLLAPSE_TICK_INTERVAL', 50),
        'ecological_collapse_instability_threshold' => (float) env('WORLDOS_ECOLOGICAL_COLLAPSE_INSTABILITY_THRESHOLD', 0.7),
        'ecological_collapse_duration_min' => (int) env('WORLDOS_ECOLOGICAL_COLLAPSE_DURATION_MIN', 200),
        'ecological_collapse_duration_max' => (int) env('WORLDOS_ECOLOGICAL_COLLAPSE_DURATION_MAX', 1000),
        'ecological_collapse_recovery_ticks' => (int) env('WORLDOS_ECOLOGICAL_COLLAPSE_RECOVERY_TICKS', 100),
        'ecological_collapse_resource_regeneration_factor' => (float) env('WORLDOS_ECOLOGICAL_COLLAPSE_RESOURCE_REGEN_FACTOR', 0.5),
        'ecological_collapse_death_probability_add' => (float) env('WORLDOS_ECOLOGICAL_COLLAPSE_DEATH_PROB_ADD', 0.1),
        'ecological_collapse_reproduction_factor' => (float) env('WORLDOS_ECOLOGICAL_COLLAPSE_REPRODUCTION_FACTOR', 0.4),
        // Ecological Phase Transition Engine (Tier 2): biome shift (forest / grassland / desert)
        'ecological_phase_transition_tick_interval' => (int) env('WORLDOS_ECOLOGICAL_PHASE_TRANSITION_TICK_INTERVAL', 100),
        'ecological_phase_transition_duration_ticks' => (int) env('WORLDOS_ECOLOGICAL_PHASE_TRANSITION_DURATION_TICKS', 50),
        'ecological_phase_transition_rainfall_desert_max' => (float) env('WORLDOS_ECOLOGICAL_PHASE_TRANSITION_RAINFALL_DESERT_MAX', 0.35),
        'ecological_phase_transition_rainfall_forest_min' => (float) env('WORLDOS_ECOLOGICAL_PHASE_TRANSITION_RAINFALL_FOREST_MIN', 0.65),
        'ecological_phase_transition_biome_resource_regen' => [
            'forest' => (float) env('WORLDOS_ECOLOGICAL_PHASE_TRANSITION_BIOME_REGEN_FOREST', 1.2),
            'grassland' => (float) env('WORLDOS_ECOLOGICAL_PHASE_TRANSITION_BIOME_REGEN_GRASSLAND', 1.0),
            'desert' => (float) env('WORLDOS_ECOLOGICAL_PHASE_TRANSITION_BIOME_REGEN_DESERT', 0.6),
        ],
        // Behavior & Decision Engine (Tier 6): needs, goal, Utility AI, execution state
        'behavior_tick_interval' => (int) env('WORLDOS_BEHAVIOR_TICK_INTERVAL', 1),
        'behavior_stagger_modulus' => (int) env('WORLDOS_BEHAVIOR_STAGGER_MODULUS', 3),
        'behavior_memory_decay_rate' => (float) env('WORLDOS_BEHAVIOR_MEMORY_DECAY_RATE', 0.01),
        // Culture Engine (Tier 7): meme pool, transmission, selection, culture_weight in behavior
        'culture_tick_interval' => (int) env('WORLDOS_CULTURE_TICK_INTERVAL', 10),
        'culture_transmission_rate' => (float) env('WORLDOS_CULTURE_TRANSMISSION_RATE', 0.15),
        'culture_mutation_rate' => (float) env('WORLDOS_CULTURE_MUTATION_RATE', 0.05),
        'culture_weight_in_behavior' => (float) env('WORLDOS_CULTURE_WEIGHT_IN_BEHAVIOR', 0.2),
        // Language Engine (Tier 8): vocabulary, intent→encode→decode, communication, memory, language groups
        'language_tick_interval' => (int) env('WORLDOS_LANGUAGE_TICK_INTERVAL', 5),
        'language_vocabulary_max_size' => (int) env('WORLDOS_LANGUAGE_VOCABULARY_MAX_SIZE', 24),
        'language_communication_probability' => (float) env('WORLDOS_LANGUAGE_COMMUNICATION_PROB', 0.2),
        'language_memory_size' => (int) env('WORLDOS_LANGUAGE_MEMORY_SIZE', 5),
        'language_memory_decay' => (float) env('WORLDOS_LANGUAGE_MEMORY_DECAY', 0.05),
        // Civilization Engine (Tier 9): settlement layer
        'civilization_tick_interval' => (int) env('WORLDOS_CIVILIZATION_TICK_INTERVAL', 20),
        'civilization_settlement_thresholds' => [
            'camp' => 0,
            'village' => 100,
            'town' => 1000,
            'city' => 10000,
            'metropolis' => 100000
        ],
        // Global Economy (Tier 10)
        'economy_tick_interval' => (int) env('WORLDOS_ECONOMY_TICK_INTERVAL', 20),
        // Politics (Tier 11)
        'politics_tick_interval' => (int) env('WORLDOS_POLITICS_TICK_INTERVAL', 25),
        // War (Tier 12)
        'war_tick_interval' => (int) env('WORLDOS_WAR_TICK_INTERVAL', 30),
        // History (Tier 13): timeline aggregation
        'history_timeline_limit' => (int) env('WORLDOS_HISTORY_TIMELINE_LIMIT', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Capability layer (Phase 1) — formulas: trait indices → capability
    |--------------------------------------------------------------------------
    | Trait indices: Dom=0, Amb=1, Coe=2, Loy=3, Emp=4, Sol=5, Con=6,
    | Pra=7, Cur=8, Dog=9, Rsk=10, Fer=11, Ven=12, Hop=13, Grf=14, Pri=15, Shm=16.
    | Each formula: array of [index => weight]; result clamped 0..1.
    */
    'capability' => [
        'intellect' => [7 => 0.4, 8 => 0.4, 9 => -0.3],  // Pra + Cur - Dog
        'charisma' => [0 => 0.35, 1 => 0.35, 4 => 0.35], // Dom + Amb + Emp
        'wealth' => [1 => 0.3, 2 => 0.4, 10 => 0.3],     // Amb + Coe + Rsk
        'followers' => [4 => 0.3, 5 => 0.35, 6 => 0.35], // Emp + Sol + Con
        'authority' => [0 => 0.4, 2 => 0.4, 15 => 0.2], // Dom + Coe + Pri
        'creativity' => [8 => 0.4, 10 => 0.3, 13 => 0.3], // Cur + Rsk + Hop
    ],

    'hero_lifecycle' => [
        'influence_rising' => (float) env('WORLDOS_HERO_INFLUENCE_RISING', 30),
        'influence_peak' => (float) env('WORLDOS_HERO_INFLUENCE_PEAK', 70),
        'myth_ticks_after_death' => (int) env('WORLDOS_HERO_MYTH_TICKS_AFTER_DEATH', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Great Person: conditions to spawn SupremeEntity (Phase H)
    |--------------------------------------------------------------------------
    | entropy_min/max: universe entropy range for spawn. min_institutions: min active institutions.
    | cooldown_ticks: min ticks since last supreme_emergence in this universe.
    */
    'great_person' => [
        'entropy_min' => (float) env('WORLDOS_GREAT_PERSON_ENTROPY_MIN', 0.3),
        'entropy_max' => (float) env('WORLDOS_GREAT_PERSON_ENTROPY_MAX', 0.75),
        'min_institutions' => (int) env('WORLDOS_GREAT_PERSON_MIN_INSTITUTIONS', 1),
        'cooldown_ticks' => (int) env('WORLDOS_GREAT_PERSON_COOLDOWN_TICKS', 500),
        'heroes_per_population' => (int) env('WORLDOS_GREAT_PERSON_HEROES_PER_POPULATION', 100000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Autonomic: fork / archive thresholds (self-fork universe)
    |--------------------------------------------------------------------------
    | fork_entropy_min: entropy >= this may trigger fork. archive_entropy_threshold: entropy >= this → archive.
    | min_ticks_before_archive: AEE/DecisionEngine sẽ không archive trước tick này (tránh archive quá sớm).
    | fork_grace_period_ticks: universe con (fork) không bị archive cho đến khi chạy đủ N tick kể từ forked_at_tick.
    | stagnation_threshold: novelty below this → mutate (stub) in AEE.
    */
    'autonomic' => [
        'fork_entropy_min' => (float) env('WORLDOS_FORK_ENTROPY_MIN', 0.5),
        'archive_entropy_threshold' => (float) env('WORLDOS_ARCHIVE_ENTROPY_THRESHOLD', 0.995),
        'min_ticks_before_archive' => (int) env('WORLDOS_MIN_TICKS_BEFORE_ARCHIVE', 150),
        'fork_grace_period_ticks' => (int) env('WORLDOS_FORK_GRACE_PERIOD_TICKS', 50),
        'stagnation_threshold' => (float) env('WORLDOS_STAGNATION_THRESHOLD', 0.1),
        'max_fork_branches' => (int) env('WORLDOS_MAX_FORK_BRANCHES', 1),
        // doc §13: merge when similarity between two universes > threshold
        'merge_similarity_threshold' => (float) env('WORLDOS_MERGE_SIMILARITY_THRESHOLD', 0.92),
        // doc §13: promote when civilization reaches milestone (0 = disabled)
        'promote_milestone_complexity' => (float) env('WORLDOS_PROMOTE_MILESTONE_COMPLEXITY', 0),
        'promote_milestone_civ_count' => (int) env('WORLDOS_PROMOTE_MILESTONE_CIV_COUNT', 0),
    ],

    'memory' => [
        'driver' => env('WORLDOS_MEMORY_DRIVER', 'db_json'),
        'max_candidates' => (int) env('WORLDOS_MEMORY_MAX_CANDIDATES', 500),
        'embedding_model' => env('WORLDOS_MEMORY_EMBEDDING_MODEL', 'hashing-384'),
        'embedding_version' => env('WORLDOS_MEMORY_EMBEDDING_VERSION', 'v1'),
    ],

];
