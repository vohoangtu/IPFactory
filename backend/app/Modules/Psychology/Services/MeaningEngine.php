<?php

namespace App\Modules\Psychology\Services;

use App\Modules\Psychology\Dsl\BehaviorDslLoader;
use App\Modules\Psychology\ValueObjects\Meaning;
use App\Modules\Psychology\ValueObjects\MemoryStream;
use App\Modules\Psychology\ValueObjects\PsychologicalState;
use App\Modules\Psychology\ValueObjects\TraitVector;

/**
 * MeaningEngine – translates raw world events into subjective actor meaning.
 *
 * THIS IS THE CORE of the Psychology Layer.
 * Same event → different meaning depending on actor's traits, memory, and social context.
 *
 * Three-layer pipeline (Lazarus Cognitive Appraisal + Freudian bias):
 *  1. Base meaning   → what this event "objectively" means (DSL lookup)
 *  2. Bias layer     → personality (Big Five) + trauma (Freudian hidden bias)
 *  3. Context layer  → social relation + recent memory (confirmation bias)
 */
final class MeaningEngine
{
    public function __construct(
        private readonly BehaviorDslLoader $dslLoader,
    ) {}

    /**
     * Interpret a world event for a specific actor context.
     *
     * @param string              $eventType    e.g. 'threat_encountered', 'social_conflict'
     * @param TraitVector         $traits       Actor Big Five personality
     * @param PsychologicalState  $state        Current emotional state
     * @param MemoryStream        $memory       Actor episodic memory
     * @param array               $socialContext ['liking' => float, 'fear_of_source' => float]
     * @return Meaning
     */
    public function interpret(
        string             $eventType,
        TraitVector        $traits,
        PsychologicalState $state,
        MemoryStream       $memory,
        array              $socialContext = [],
    ): Meaning {
        // Step 1: Base meaning from DSL event_meanings table
        $base = $this->baseMeaning($eventType);

        // Step 2: Personality + Trauma bias (Freud hidden layer)
        $biased = $this->applyTraitBias($base, $traits, $memory, $state);

        // Step 3: Social context and memory confirmation bias
        $final = $this->applySocialContext($biased, $socialContext, $memory);

        return $final;
    }

    /**
     * Aggregate interpretation for macro-level simulation (zone/civilization level).
     * Used when no individual actor exists – derives context from WorldState metrics.
     *
     * @param array $zoneMetrics ['entropy' => float, 'fear' => float, 'trauma' => float, ...]
     * @param TraitVector|null $traits Neutral if null
     */
    public function interpretFromZoneMetrics(
        array       $zoneMetrics,
        ?TraitVector $traits = null,
    ): Meaning {
        $traits    = $traits ?? TraitVector::neutral();
        $entropy   = (float) ($zoneMetrics['entropy']  ?? 0.0);
        $fearLevel = (float) ($zoneMetrics['fear']     ?? 0.0);
        $trauma    = (float) ($zoneMetrics['trauma']   ?? 0.0);

        // Derive event type from dominant zone signal
        $eventType = match (true) {
            $entropy > 0.7 || $fearLevel > 0.7 => 'catastrophe',
            $fearLevel > 0.4                   => 'threat_encountered',
            $entropy > 0.4                     => 'social_conflict',
            $trauma > 0.5                      => 'threat_encountered',
            default                            => 'default',
        };

        $base = $this->baseMeaning($eventType);

        // Scale intensity by zone metrics
        $intensityBoost = ($entropy + $fearLevel + $trauma) / 3.0;
        return $base->withAdjustment(
            valenceDelta:   -($trauma * 0.2),
            intensityDelta: $intensityBoost * 0.3,
        );
    }

    // ─────────────────── Private ───────────────────

    private function baseMeaning(string $eventType): Meaning
    {
        $mappings = $this->dslLoader->eventMeanings();
        $def = $mappings[$eventType] ?? $mappings['default'] ?? null;

        if ($def === null) {
            return Meaning::neutral();
        }

        return new Meaning(
            type:      $def['type']     ?? Meaning::TYPE_NEUTRAL,
            valence:   (float)($def['valence']   ?? 0.0),
            intensity: (float)($def['intensity'] ?? 0.1),
            certainty: (float)($def['certainty'] ?? 0.5),
            tags:      $def['tags'] ?? [],
        );
    }

    /**
     * Apply Big Five traits + Freudian trauma bias.
     *
     * Key relationships:
     * - High neuroticism → more negative perception (amplify threat)
     * - High confidence  → buffer against negative valence
     * - Trauma memory    → amplify negative meaning (hidden bias)
     */
    private function applyTraitBias(
        Meaning            $base,
        TraitVector        $traits,
        MemoryStream       $memory,
        PsychologicalState $state,
    ): Meaning {
        $valenceDelta = 0.0;

        // Neuroticism: neurotic actors interpret events more negatively
        if ($base->valence < 0) {
            $valenceDelta -= $traits->neuroticism() * 0.25;
        }

        // Confidence buffers negative valence
        $valenceDelta += $traits->confidence() * 0.2 * ($base->valence < 0 ? 1 : 0);


        // Freudian trauma: suppressed memories distort perception downward
        $traumaBias = $memory->traumaTotal();
        $valenceDelta -= $traumaBias * 0.3;

        // Current fear amplifies threat perception
        $intensityDelta = $state->fear * 0.2;

        return $base->withAdjustment($valenceDelta, $intensityDelta);
    }

    /**
     * Apply social relationship and memory context bias.
     *
     * - Trust/liking toward event source → buffer negative meaning
     * - Fear of source → amplify threat
     * - Recent memory avg → confirmation bias
     */
    private function applySocialContext(
        Meaning      $biased,
        array        $socialContext,
        MemoryStream $memory,
    ): Meaning {
        $liking       = (float) ($socialContext['liking']       ?? 0.0);
        $fearOfSource = (float) ($socialContext['fear_of_source'] ?? 0.0);

        $valenceDelta   = $liking * 0.3;         // trust/liking reduces negative impact
        $intensityDelta = $fearOfSource * 0.2;   // fear of source amplifies intensity

        // Memory confirmation bias: past valence biases current interpretation
        $valenceDelta += $memory->recentBias(5) * 0.1;

        return $biased->withAdjustment($valenceDelta, $intensityDelta);
    }
}
