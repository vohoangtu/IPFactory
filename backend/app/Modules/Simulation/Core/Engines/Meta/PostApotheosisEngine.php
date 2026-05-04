<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

/**
 * Phase 63 (V10+ Vector 1): Post-Apotheosis Consciousness Field Engine 👁️🔝
 *
 * "Khi nền văn minh đạt TRANSCENDENCE, họ trở thành Người Quan Sát —
 * ý thức tập thể của họ trở thành một lực lượng vật lý bẻ cong thực tại."
 *
 * Hoạt động:
 *  1. Tính toán collective consciousness từ meditating actors + meaning systems
 *  2. Phản hồi consciousness field lên entropy, stability, và causal_integrity
 *  3. Ở ngưỡng TRANSCENDENCE cao nhất → reality_programming_factor kích hoạt
 */
class PostApotheosisEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    private const MEDITATION_FIELD_BONUS = 0.02;
    private const APOTHEOSIS_ENTROPY_DAMPING = 0.03;
    private const TRANSCENDENCE_THRESHOLD = 0.85;

    public function name(): string
    {
        return 'post_apotheosis';
    }

    public function phase(): string
    {
        return 'meta';
    }

    public function priority(): int
    {
        return 14;
    }

    public function tickRate(): int
    {
        return 5;
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $activeAttractor = $state->getActiveAttractor();
        $fields = $state->getFields();
        $tick = $ctx->getTick();

        // ── 1. Compute Collective Consciousness Field ──
        $consciousnessField = $this->computeConsciousnessField($state, $fields);
        $state->set('meta.consciousness_field', round($consciousnessField, 4));

        // ── 2. Always-on: Consciousness feedback on physics ──
        // Even pre-transcendence, collective consciousness weakly influences entropy
        if ($consciousnessField > 0.4) {
            $currentEntropy = (float) $state->get('entropy', 0.5);
            $damping = self::APOTHEOSIS_ENTROPY_DAMPING * ($consciousnessField - 0.4);
            $newEntropy = max(0.0, $currentEntropy - $damping);
            $state->set('entropy', round($newEntropy, 4));
        }

        // ── 3. Full Apotheosis — only when TRANSCENDENCE attractor active ──
        if ($activeAttractor !== 'TRANSCENDENCE') {
            return EngineResult::empty();
        }

        $meaning  = (float) ($fields['meaning']  ?? 0.0);
        $knowledge = (float) ($fields['knowledge'] ?? 0.0);
        $resonance = (float) ($fields['resonance'] ?? 0.0);

        // ── 4. Observer Effect: mass meditation bends reality constants ──
        if ($consciousnessField >= self::TRANSCENDENCE_THRESHOLD) {
            $realityFactor = ($meaning + $knowledge + $resonance) / 3.0;
            $state->set('meta.meta_observation_active', true);
            $state->set('meta.reality_programming_factor', round($realityFactor, 4));

            // Bend axiom physics based on how advanced the transcendence is
            $this->applyRealityBending($state, $realityFactor);

            Log::info("PostApotheosisEngine: FULL TRANSCENDENCE — Reality reprogramming at factor {$realityFactor}", [
                'universe_id'   => $ctx->getUniverseId(),
                'tick'          => $tick,
                'consciousness' => $consciousnessField,
            ]);
        }

        // ── 5. Partial Observer ── (0.6 < field < threshold)
        elseif ($consciousnessField >= 0.6) {
            $state->set('meta.meta_observation_active', false);
            $state->set('meta.observer_emergence', true);
            $state->set('meta.partial_observation_factor', round($consciousnessField * 0.8, 4));

            // Partial reality bending — only stabilize
            $currentStability = (float) $state->get('stability_index', 0.5);
            $state->set('stability_index', min(1.0, $currentStability + 0.01));
        }

        return EngineResult::empty();
    }

    /**
     * Calculate the composite consciousness field.
     * Sources: meditating-actor count, meaning_systems coherence, resonance field.
     */
    private function computeConsciousnessField(WorldState $state, array $fields): float
    {
        $resonance     = (float) ($fields['resonance'] ?? 0.0);
        $meaning       = (float) ($fields['meaning']   ?? 0.0);
        $meaningSystems = (array) $state->get('meta.meaning_systems', []);
        $meditatingActors = (int) $state->get('meta.meditating_actor_count', 0);

        // Coherence-weighted belief strength
        $beliefStrength = 0.0;
        foreach ($meaningSystems as $system) {
            $coherence  = (float) ($system['coherence']  ?? 0.5);
            $influence  = (float) ($system['influence']  ?? 0.0);
            $beliefStrength += $coherence * $influence;
        }
        $normalizedBelief = count($meaningSystems) > 0
            ? $beliefStrength / count($meaningSystems)
            : 0.0;

        // Meditation effect: each meditating actor adds a small bonus
        $meditationBonus = min(0.3, $meditatingActors * self::MEDITATION_FIELD_BONUS);

        // Final weighted composite
        return min(1.0, $resonance * 0.3 + $meaning * 0.3 + $normalizedBelief * 0.25 + $meditationBonus);
    }

    /**
     * Apply reality-bending axiom modifications at full transcendence.
     */
    private function applyRealityBending(WorldState $state, float $factor): void
    {
        // Reduce observation load (less uncertainty in a conscious universe)
        $observationLoad = (float) $state->get('observation_load', 0.5);
        $state->set('observation_load', max(0.0, $observationLoad - 0.02 * $factor));

        // Increase causal integrity (consciousness maintains reality)
        $causalIntegrity = (float) $state->get('causal_integrity', 1.0);
        $state->set('causal_integrity', min(1.0, $causalIntegrity + 0.01 * $factor));

        // Very high transcendence → axiom evolution signal
        if ($factor > 0.9) {
            $state->set('meta.axiom_evolution_ready', true);
            $state->set('meta.axiom_evolution_type', 'CONSCIOUSNESS_REWRITE');
        }
    }
}
