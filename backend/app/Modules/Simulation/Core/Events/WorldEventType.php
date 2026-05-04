<?php

namespace App\Modules\Simulation\Core\Events;

/**
 * World event types (doc §16). Grouped by topic for Event Bus / Kafka topics.
 */
final class WorldEventType
{
    // Civilization
    public const CIVILIZATION_BORN = 'civilization_born';
    public const CIVILIZATION_EXPAND = 'civilization_expand';
    public const CIVILIZATION_SPLIT = 'civilization_split';
    public const CIVILIZATION_COLLAPSE = 'civilization_collapse';
    public const CAPITAL_MOVED = 'capital_moved';
    public const ACTOR_BORN = 'actor_born';

    // War
    public const WAR_DECLARED = 'war_declared';
    public const BATTLE_FOUGHT = 'battle_fought';
    public const CITY_SIEGED = 'city_sieged';
    public const PEACE_TREATY = 'peace_treaty';
    public const EMPIRE_FALL = 'empire_fall';
    public const ZONE_CONFLICT = 'zone_conflict';

    // Religion
    public const RELIGION_FOUNDED = 'religion_founded';
    public const RELIGION_SPLIT = 'religion_split';
    public const RELIGIOUS_REFORM = 'religious_reform';
    public const RELIGION_SPREAD = 'religion_spread';
    public const HOLY_WAR = 'holy_war';

    // Technology
    public const TECHNOLOGY_INVENTED = 'technology_invented';
    public const TECHNOLOGY_DIFFUSED = 'technology_diffused';
    public const TECH_REVOLUTION = 'tech_revolution';
    public const SCIENTIFIC_BREAKTHROUGH = 'scientific_breakthrough';

    // Culture
    public const ART_MOVEMENT_BORN = 'art_movement_born';
    public const CULTURAL_GOLDEN_AGE = 'cultural_golden_age';
    public const LITERARY_REVOLUTION = 'literary_revolution';
    public const ARCHITECTURAL_STYLE_BORN = 'architectural_style_born';
    public const CULTURAL_DRIFT = 'cultural_drift';

    // Economy
    public const TRADE_ROUTE_ESTABLISHED = 'trade_route_established';
    public const MARKET_CRASH = 'market_crash';
    public const ECONOMIC_BOOM = 'economic_boom';
    public const CURRENCY_CREATED = 'currency_created';

    // Diplomacy
    public const TREATY_EXPIRED = 'treaty_expired';

    // Population
    public const MIGRATION_WAVE = 'migration_wave';
    public const POPULATION_BOOM = 'population_boom';
    public const FAMINE = 'famine';
    public const PLAGUE_OUTBREAK = 'plague_outbreak';
    public const CROP_FAILURE = 'crop_failure';

    // Ideology
    public const IDEOLOGY_BORN = 'ideology_born';
    public const PHILOSOPHY_SCHOOL = 'philosophy_school';
    public const POLITICAL_REVOLUTION = 'political_revolution';
    public const STABILITY_CRISIS = 'stability_crisis';
    public const CONSTITUTION_WRITTEN = 'constitution_written';

    // Simulation / kernel
    public const STRUCTURAL_DECAY = 'structural_decay';
    public const PHASE_TRANSITION = 'phase_transition';
    public const ECOLOGICAL_COLLAPSE = 'ecological_collapse';
    public const ACTOR_DIED = 'actor_died';
    public const SPECIES_EXTINCT = 'species_extinct';
    public const ZONE_PRESSURES_UPDATED = 'zone_pressures_updated';
    public const PRESSURE_UPDATE = 'pressure_update';
    public const WORLD_RULES_MUTATED = 'world_rules_mutated';
    public const TOPOLOGY_REWIRED = 'topology_rewired';

    /** @return string[] */
    public static function all(): array
    {
        return [
            self::CIVILIZATION_BORN, self::CIVILIZATION_EXPAND, self::CIVILIZATION_SPLIT,
            self::CIVILIZATION_COLLAPSE, self::CAPITAL_MOVED,
            self::WAR_DECLARED, self::BATTLE_FOUGHT, self::CITY_SIEGED, self::PEACE_TREATY,
            self::EMPIRE_FALL, self::ZONE_CONFLICT,
            self::RELIGION_FOUNDED, self::RELIGION_SPLIT, self::RELIGIOUS_REFORM,
            self::RELIGION_SPREAD, self::HOLY_WAR,
            self::TECHNOLOGY_INVENTED, self::TECHNOLOGY_DIFFUSED, self::TECH_REVOLUTION,
            self::SCIENTIFIC_BREAKTHROUGH,
            self::ART_MOVEMENT_BORN, self::CULTURAL_GOLDEN_AGE, self::LITERARY_REVOLUTION,
            self::ARCHITECTURAL_STYLE_BORN, self::CULTURAL_DRIFT,
            self::TRADE_ROUTE_ESTABLISHED, self::MARKET_CRASH, self::ECONOMIC_BOOM, self::CURRENCY_CREATED,
            self::TREATY_EXPIRED,
            self::MIGRATION_WAVE, self::POPULATION_BOOM, self::FAMINE, self::PLAGUE_OUTBREAK, self::CROP_FAILURE,
            self::IDEOLOGY_BORN, self::PHILOSOPHY_SCHOOL, self::POLITICAL_REVOLUTION, self::STABILITY_CRISIS, self::CONSTITUTION_WRITTEN,
            self::STRUCTURAL_DECAY, self::PHASE_TRANSITION, self::ECOLOGICAL_COLLAPSE,
            self::ACTOR_DIED, self::ACTOR_BORN, self::SPECIES_EXTINCT,
            self::ZONE_PRESSURES_UPDATED, self::PRESSURE_UPDATE, self::WORLD_RULES_MUTATED, self::TOPOLOGY_REWIRED,
        ];
    }

    /** Topic for Kafka-style routing (e.g. world.events.civilization) */
    public static function topicFor(string $type): string
    {
        $map = [
            self::CIVILIZATION_BORN => 'civilization', self::CIVILIZATION_EXPAND => 'civilization',
            self::CIVILIZATION_SPLIT => 'civilization', self::CIVILIZATION_COLLAPSE => 'civilization',
            self::CAPITAL_MOVED => 'civilization',
            self::WAR_DECLARED => 'war', self::BATTLE_FOUGHT => 'war', self::CITY_SIEGED => 'war',
            self::PEACE_TREATY => 'war', self::EMPIRE_FALL => 'war', self::ZONE_CONFLICT => 'war',
            self::RELIGION_FOUNDED => 'religion', self::RELIGION_SPLIT => 'religion',
            self::RELIGIOUS_REFORM => 'religion', self::RELIGION_SPREAD => 'religion', self::HOLY_WAR => 'religion',
            self::TECHNOLOGY_INVENTED => 'tech', self::TECHNOLOGY_DIFFUSED => 'tech',
            self::TECH_REVOLUTION => 'tech', self::SCIENTIFIC_BREAKTHROUGH => 'tech',
            self::ART_MOVEMENT_BORN => 'culture', self::CULTURAL_GOLDEN_AGE => 'culture',
            self::LITERARY_REVOLUTION => 'culture', self::ARCHITECTURAL_STYLE_BORN => 'culture',
            self::CULTURAL_DRIFT => 'culture',
            self::TRADE_ROUTE_ESTABLISHED => 'economy', self::MARKET_CRASH => 'economy',
            self::ECONOMIC_BOOM => 'economy', self::CURRENCY_CREATED => 'economy',
            self::TREATY_EXPIRED => 'diplomacy',
            self::MIGRATION_WAVE => 'population', self::POPULATION_BOOM => 'population',
            self::FAMINE => 'population', self::PLAGUE_OUTBREAK => 'population', self::CROP_FAILURE => 'population',
            self::IDEOLOGY_BORN => 'ideology', self::PHILOSOPHY_SCHOOL => 'ideology',
            self::POLITICAL_REVOLUTION => 'ideology', self::STABILITY_CRISIS => 'ideology', self::CONSTITUTION_WRITTEN => 'ideology',
            self::STRUCTURAL_DECAY => 'simulation', self::PHASE_TRANSITION => 'simulation',
            self::ECOLOGICAL_COLLAPSE => 'simulation', self::ACTOR_DIED => 'simulation',
            self::ACTOR_BORN => 'simulation',
            self::SPECIES_EXTINCT => 'simulation',
            self::ZONE_PRESSURES_UPDATED => 'simulation', self::PRESSURE_UPDATE => 'simulation',
            self::WORLD_RULES_MUTATED => 'simulation', self::TOPOLOGY_REWIRED => 'simulation',
        ];
        return 'world.events.' . ($map[$type] ?? 'simulation');
    }
}
