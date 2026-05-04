<?php

namespace App\Modules\Psychology\Services;

use App\Modules\Psychology\ValueObjects\Meaning;
use App\Modules\Psychology\ValueObjects\MemoryItem;
use App\Modules\Psychology\ValueObjects\MemoryStream;
use App\Modules\Psychology\ValueObjects\PsychologicalState;

/**
 * StateEvolutionService – the CBT (Cognitive Behavioral Therapy) loop.
 *
 * Event → Perception → EmotionUpdate → Memory → (next Event)
 *
 * Rules:
 * - All emotions DECAY toward baseline each tick (CBT inertia)
 * - Events cause emotion DELTAS (not reset)
 * - High-intensity events → trauma memories
 * - Repeated negative events → cognitive drift (trust erodes)
 * - State is clamped to [0,1] always
 */
final class StateEvolutionService
{
    private const DECAY_RATE = 0.95;

    /**
     * Full tick evolution: decay, then apply new event meaning.
     *
     * @param PsychologicalState $state    Current state (mutated in place)
     * @param Meaning            $meaning  Interpretation of current event
     * @param MemoryStream       $memory   Actor's memory (will receive new item)
     * @param float              $stressDelta  Additional stress from ConflictResolver
     * @param int                $tick     Current simulation tick
     */
    public function evolve(
        PsychologicalState $state,
        Meaning            $meaning,
        MemoryStream       $memory,
        float              $stressDelta = 0.0,
        int                $tick = 0,
    ): void {
        // Step 1: Decay all emotions toward baseline (CBT: emotions are temporary)
        $state->decay(self::DECAY_RATE);

        // Step 2: Calculate emotion deltas from Meaning
        $delta = $this->computeEmotionDelta($meaning, $state);

        // Step 3: Apply conflict stress
        $delta['stress'] = ($delta['stress'] ?? 0.0) + $stressDelta;

        // Step 4: Apply combined delta
        $state->applyDelta($delta);

        // Step 5: Cognitive drift (long-term trust erosion under repeated threat)
        if ($meaning->threatLevel() > 0.6) {
            $state->applyDelta(['trust' => -0.03]);
        }
        if ($meaning->valence > 0.5) {
            $state->applyDelta(['trust' => +0.02]);
        }

        // Step 6: Store memory
        $memoryType = $this->classifyMemory($meaning);
        $memory->push(MemoryItem::fromEvent(
            type:      $memoryType,
            valence:   $meaning->valence,
            intensity: $meaning->intensity,
            tick:      $tick,
        ));
    }

    /**
     * Evolve state from zone-level metrics (aggregate, macro mode).
     * No individual event – driven by environmental pressures.
     *
     * @param PsychologicalState $state
     * @param array $zoneMetrics ['entropy' => float, 'fear' => float, 'trauma' => float, 'inequality' => float]
     */
    public function evolveFromZoneMetrics(
        PsychologicalState $state,
        array              $zoneMetrics,
    ): void {
        $state->decay(self::DECAY_RATE);

        $entropy    = (float) ($zoneMetrics['entropy']    ?? 0.0);
        $fear       = (float) ($zoneMetrics['fear']       ?? 0.0);
        $trauma     = (float) ($zoneMetrics['trauma']     ?? 0.0);
        $inequality = (float) ($zoneMetrics['inequality'] ?? 0.0);

        $state->applyDelta([
            'fear'    => $entropy * 0.3 + $fear * 0.3,
            'stress'  => $fear * 0.2 + $inequality * 0.2,
            'sadness' => $trauma * 0.15,
            'trust'   => -($inequality * 0.1),
        ]);
    }

    // ─────────────────── Private ───────────────────

    /**
     * Calculate emotion deltas from meaning interpretation.
     * Based on CBT: threat → anger + fear, positive → joy, loss → sadness.
     */
    private function computeEmotionDelta(Meaning $meaning, PsychologicalState $current): array
    {
        $delta   = [];
        $threat  = $meaning->threatLevel();
        $valence = $meaning->valence;

        // Anger: high threat + negative valence
        if ($threat > 0.4) {
            $delta['anger'] = $threat * 0.3;
        }

        // Fear: direct threat signal
        if ($threat > 0.3) {
            $delta['fear'] = $threat * 0.25;
        }

        // Joy: positive valence
        if ($valence > 0.3) {
            $delta['joy'] = $valence * 0.3;
            $delta['anger'] = ($delta['anger'] ?? 0.0) - $valence * 0.05; // joy reduces anger
        }

        // Sadness: negative valence with low arousal (not acute threat, but loss)
        if ($valence < -0.3 && $threat < 0.5) {
            $delta['sadness'] = abs($valence) * 0.25;
        }

        // Stress: any high-intensity event is stressful
        $delta['stress'] = $meaning->intensity * 0.15;

        return $delta;
    }

    private function classifyMemory(Meaning $meaning): string
    {
        if ($meaning->intensity > 0.8 && $meaning->valence < -0.5) {
            return MemoryItem::TYPE_TRAUMA;
        }
        if (in_array('social_threat', $meaning->tags, true)) {
            return MemoryItem::TYPE_BETRAYAL;
        }
        if ($meaning->valence > 0.5) {
            return MemoryItem::TYPE_REWARD;
        }
        if (in_array('social', $meaning->tags, true)) {
            return MemoryItem::TYPE_SOCIAL;
        }
        return MemoryItem::TYPE_NEUTRAL;
    }
}
