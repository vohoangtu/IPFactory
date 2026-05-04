<?php

namespace App\Contracts;

/**
 * Interface for calling the WorldOS simulation engine (Rust gRPC).
 * Phase 1: stub. Phase 3: implement with real gRPC client.
 */
interface SimulationEngineClientInterface
{
    /**
     * Advance universe by N ticks, return snapshot.
     *
     * @param  int  $universeId
     * @param  int  $ticks
     * @param  array  $stateInput  Optional state vector array.
     * @param  array|null  $worldConfig Optional world metadata (origin, axioms, etc.)
     * @return array{ok: bool, snapshot?: array, error_message?: string}
     */
    public function advance(int $universeId, int $ticks, array $stateInput = [], ?array $worldConfig = null): array;

    /**
     * Merge two universes into one.
     */
    public function merge(string $stateA, string $stateB): array;

    /**
     * Run N simulations in a single call.
     *
     * @param  array  $requests  Array of advance requests.
     * @return array{responses: array}
     */
    public function batchAdvance(array $requests): array;

    /**
     * Analyze a trajectory (recurrence matrix, Lyapunov, etc.)
     *
     * @param  array  $points  Array of {tick, state} points.
     * @param  float  $threshold  Recurrence threshold (default 0.1).
     * @return array{is_strange_attractor: bool, is_bounded: bool, recurrence_rate: float, max_lyapunov_estimate: float, trajectory_variance: float, basin_center: array, basin_radius: float, regime_transitions: array}
     */
    public function analyzeTrajectory(array $points, float $threshold = 0.1): array;

    /**
     * Evaluate DSL rules against world state (Rule VM in Rust).
     * Returns list of events and state adjustments to apply.
     *
     * @param  array  $state  World state (state_vector + entropy, stability_index, etc.)
     * @param  string|null  $rulesDsl  Optional DSL text; if null/empty, uses no rules
     * @return array{ok: bool, outputs: array, error_message?: string}
     */
    public function evaluateRules(array $state, ?string $rulesDsl = null): array;

    /**
     * Vectorized Actor Processing (SoA - Structure of Arrays).
     * Replaces FfiActorEngine::processActorsSoa.
     * 
     * @return array{ok: bool, outputs: array, scars: array, spawned_actors: array, error_message?: string}
     */
    public function processActorsSoa(
        int $tick,
        array $ids,
        array $zoneIds,
        array $hunger,
        array $energy,
        array $fear,
        array $trauma,
        array $heroicTypes,
        array $lineageIds,
        array $memes,
        array $traitsMatrix,
        array $behaviorStates = [],
        array $behaviorGraphs = [],
        array $archetypes = [],
        array $socialGraph = [],
        array $edicts = [],
        array $factionIds = [],
        array $factionLoyalty = [],
        bool $isObserved = false,
        array $narrativeContext = [],
        array $factionRelations = [],
        array $beliefDefinitions = [],
        array $beliefAlignments = [],
        array $techDefinitions = [],
        array $actorTechLevels = []
    ): array;

    /**
     * Vectorized Field Processing (Diffusion/Preservation).
     * Replaces FfiActorEngine::processFieldsV7.
     */
    public function processFieldsV7(
        array $fields, 
        array $neighborCounts, 
        array $neighborOffsets,
        array $neighbors, 
        float $diffusionRate, 
        float $preservationRate
    ): array;

    /**
     * Grid-Based Metabolic Calculation.
     * Replaces FfiRuleEngine::computeMetabolismGrid.
     */
    public function computeMetabolismGrid(
        array $populations, 
        array $biomasses, 
        array $industries, 
        float $efficiency, 
        float $baseEnergy
    ): array;

    /**
     * Calculate Vocation Alignment.
     * Replaces FfiActorEngine::calculateVocationAlignment.
     */
    public function calculateVocationAlignment(array $actorMotivation, array $targetProfile): float;

    /**
     * Get Combined Ruleset Gravity.
     * Replaces FfiActorEngine::getCombinedGravity.
     */
    public function getCombinedGravity(array $rulesets): float;
}
