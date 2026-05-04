<?php

namespace App\Modules\Simulation\Services\Society;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Intelligence\Models\Actor;
use App\Modules\Institutions\Models\InstitutionalEntity;
use App\Modules\Narrative\Models\Chronicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ActorCognitiveService – computes 3 cognitive variables per universe each tick.
 *
 * These 3 variables cause civilization archetypes to self-emerge without scripting:
 *   destiny_gradient   → high = religion, causal_trajectory, chosen heroes
 *   causal_curiosity   → high = science, technology, engineering
 *   anomaly_sensitivity → high = magic systems, myth, supernatural beliefs
 *
 * Combined combinations:
 *   high destiny + low curiosity + high anomaly  → religion / theocracy
 *   low destiny  + high curiosity + low anomaly  → scientific civilization
 *   high destiny + high curiosity + high anomaly → alchemy / mystic science (magic)
 *
 * Also computes existential_tension (Meaning Pressure):
 *   tension = entropy + death_events + anomaly_burst − cultural_coherence
 *   tension > 0.7 → triggers meaning_crisis event (philosophy, ideology, nihilism)
 */
class ActorCognitiveService
{
    /** Threshold above which destiny → causal_trajectory/religion events */
    const DESTINY_THRESHOLD = 0.75;

    /** Threshold above which curiosity → scientific_revolution event */
    const CURIOSITY_THRESHOLD = 0.72;

    /** Threshold above which existential_tension → meaning_crisis event */
    const MEANING_CRISIS_THRESHOLD = 0.70;

    /**
     * Compute cognitive aggregate for the universe and store in state_vector.
     * Returns the cognitive aggregate array.
     */
    public function computeAndStore(Universe $universe, UniverseSnapshot $snapshot): array
    {
        $state   = (array) ($snapshot->state_vector ?? []);
        $metrics = (array) ($snapshot->metrics ?? []);
        $vec     = array_merge($state, $metrics);

        $cognitive = $this->computeCognitiveAggregate($universe, $vec);

        // Write to universe state_vector
        $uvec = (array) ($universe->state_vector ?? []);
        $uvec['cognitive_aggregate'] = $cognitive;
        $universe->state_vector = $uvec;
        $universe->save();

        // Trigger events from cognitive thresholds
        $this->checkAndTriggerEvents($universe, $cognitive);

        return $cognitive;
    }

    /**
     * Compute the 3 cognitive variables + existential_tension.
     */
    public function computeCognitiveAggregate(Universe $universe, array $state): array
    {
        $entropy          = (float) ($state['entropy'] ?? $state['global_entropy'] ?? 0.5);
        $stabilityIndex   = (float) ($state['stability_index'] ?? $state['sci'] ?? 0.5);
        $culturalCoherence = (float) ($state['cultural_coherence'] ?? 0.4);
        $anomalyEvents    = (int)   ($state['anomaly_events_count'] ?? 0);
        $meaningField     = (float) ($state['fields']['meaning'] ?? 0.3);
        $knowledgeField   = (float) ($state['fields']['knowledge'] ?? 0.3);
        $hardtechHint     = (float) ($state['cognitive_aggregate']['hardtech_hint'] ?? 0.4);

        // --- Actor trait averages from DB ---
        $actorStats = $this->getActorTraitAverages($universe->id);

        // destiny_gradient = success_momentum (sci) + meaning_field - entropy
        // + boost from actor dogmatism (people who believe in destiny)
        $successMomentum = $stabilityIndex;
        $destinyGradient = $this->clamp(
            $successMomentum * 0.35
            + $meaningField * 0.30
            + (float)($actorStats['dogmatism'] ?? 0.5) * 0.20
            - $entropy * 0.15
        );

        // causal_curiosity = knowledge_field + tech alignment + curiosity trait avg
        $causalCuriosity = $this->clamp(
            $knowledgeField * 0.35
            + $hardtechHint * 0.30
            + (float)($actorStats['curiosity'] ?? 0.5) * 0.25
            + $stabilityIndex * 0.10
        );

        // anomaly_sensitivity = anomaly events × weight + meaning_field + fear trait
        $anomalyBoost = min(1.0, $anomalyEvents * 0.10);
        $anomalySensitivity = $this->clamp(
            $anomalyBoost * 0.40
            + $meaningField * 0.30
            + (float)($actorStats['fear'] ?? 0.4) * 0.30
        );

        // existential_tension = entropy + anomaly_burst - cultural_coherence
        // Represents the Meaning Pressure driving philosophical/religious movements
        $existentialTension = $this->clamp(
            $entropy * 0.40
            + $anomalyBoost * 0.25
            + (1.0 - $culturalCoherence) * 0.25
            + (1.0 - $stabilityIndex) * 0.10
        );

        // Civilization tendency from cognitive profile
        $tendency = $this->detectCivilizationTendency($destinyGradient, $causalCuriosity, $anomalySensitivity);

        // Doc §21: MentalState (beliefs, goals, emotions), PerceptionState, cognitive_biases
        $mentalState = $this->computeMentalState($destinyGradient, $causalCuriosity, $anomalySensitivity, $existentialTension, $actorStats, $tendency);
        $perceptionState = $this->computePerceptionState($state, $culturalCoherence, $anomalyEvents);
        $cognitiveBiases = $this->computeCognitiveBiases($actorStats);

        return [
            'destiny_gradient'    => $destinyGradient,
            'causal_curiosity'    => $causalCuriosity,
            'anomaly_sensitivity' => $anomalySensitivity,
            'existential_tension' => $existentialTension,
            'civilization_tendency' => $tendency,
            'mental_state'       => $mentalState,
            'perception_state'   => $perceptionState,
            'cognitive_biases'   => $cognitiveBiases,
        ];
    }

    /**
     * Detect the civilization tendency from cognitive combination.
     * This is the emergent civilization archetype based on belief dynamics.
     */
    protected function detectCivilizationTendency(float $destiny, float $curiosity, float $anomaly): string
    {
        if ($destiny > 0.7 && $curiosity < 0.4 && $anomaly > 0.6) return 'theocracy_prone';
        if ($curiosity > 0.7 && $destiny < 0.4 && $anomaly < 0.4) return 'scientific_prone';
        if ($destiny > 0.65 && $curiosity > 0.6 && $anomaly > 0.6) return 'mystic_science_prone'; // magic
        if ($destiny > 0.7 && $anomaly > 0.7)                       return 'prophetic_empire_prone';
        if ($curiosity > 0.7 && $anomaly > 0.6)                     return 'techno_mystical_prone';
        return 'balanced';
    }

    /**
     * Check cognitive thresholds and trigger chronicle events.
     */
    protected function checkAndTriggerEvents(Universe $universe, array $cognitive): void
    {
        $tick = $universe->current_tick ?? 0;

        // Meaning Crisis: existential_tension > 0.70
        if ($cognitive['existential_tension'] >= self::MEANING_CRISIS_THRESHOLD) {
            $this->emitEvent($universe, $tick, 'meaning_crisis', [
                'existential_tension' => $cognitive['existential_tension'],
                'tendency'            => $cognitive['civilization_tendency'],
                'description'         => 'Áp lực ý nghĩa tồn tại vượt ngưỡng — philosophy, ideology, hoặc nihilism đang nổi lên.',
            ]);
        }

        // CausalTrajectory Surge: high destiny → religion / chosen one
        if ($cognitive['destiny_gradient'] >= self::DESTINY_THRESHOLD) {
            $this->emitEvent($universe, $tick, 'causal_trajectory_surge', [
                'destiny_gradient' => $cognitive['destiny_gradient'],
                'description'      => 'Thiên mệnh gradient cao — causal_trajectory, giáo phái, hoặc anh hùng được chọn đang xuất hiện.',
            ]);
        }

        // Scientific Revolution: high curiosity
        if ($cognitive['causal_curiosity'] >= self::CURIOSITY_THRESHOLD) {
            $this->emitEvent($universe, $tick, 'scientific_revolution', [
                'causal_curiosity' => $cognitive['causal_curiosity'],
                'description'      => 'Tò mò nhân quả vượt ngưỡng — cuộc cách mạng khoa học hoặc đổi mới công nghệ đang đến.',
            ]);
        }
    }

    protected function emitEvent(Universe $universe, int $tick, string $type, array $payload): void
    {
        // Throttle: only emit once per 20 ticks to avoid spam
        $recent = Chronicle::where('universe_id', $universe->id)
            ->where('type', $type)
            ->where('from_tick', '>=', $tick - 20)
            ->exists();

        if ($recent) return;

        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick'   => $tick,
            'to_tick'     => $tick,
            'type'        => $type,
            'raw_payload' => array_merge($payload, ['action' => 'cognitive_event']),
        ]);

        Log::info("ActorCognitiveService: [{$type}] triggered in Universe #{$universe->id} at tick {$tick}.");
    }

    /** Doc §21: aggregate MentalState — beliefs, goals, emotions. */
    protected function computeMentalState(float $destiny, float $curiosity, float $anomaly, float $tension, array $actorStats, string $tendency): array
    {
        $beliefs = $tendency === 'theocracy_prone' ? ['destiny', 'causal_trajectory'] : ($tendency === 'scientific_prone' ? ['causality', 'evidence'] : ['mixed']);
        $goals = $tension > 0.6 ? ['meaning', 'survival'] : ['growth', 'stability'];
        return [
            'beliefs' => $beliefs,
            'goals'   => $goals,
            'emotions' => [
                'fear'  => $this->clamp((float)($actorStats['fear'] ?? 0.4) + $anomaly * 0.2),
                'anger' => $this->clamp((1.0 - (float)($actorStats['curiosity'] ?? 0.5)) * 0.5),
                'hope'  => $this->clamp($destiny * 0.4 + (float)($actorStats['curiosity'] ?? 0.5) * 0.3),
                'pride' => $this->clamp($destiny * 0.3),
            ],
        ];
    }

    /** Doc §21: PerceptionState — information_accuracy, rumor influence. */
    protected function computePerceptionState(array $state, float $culturalCoherence, int $anomalyEvents): array
    {
        $accuracy = max(0.2, min(1.0, $culturalCoherence * 0.8 + (1.0 - min(1.0, $anomalyEvents * 0.05)) * 0.4));
        $rumorCount = (int) ($state['idea_diffusion']['rumor_count'] ?? 0);
        return [
            'information_accuracy' => round($accuracy, 4),
            'rumors' => $rumorCount,
        ];
    }

    /** Doc §21: cognitive_biases (aggregate indices from traits). */
    protected function computeCognitiveBiases(array $actorStats): array
    {
        $dogmatism = (float)($actorStats['dogmatism'] ?? 0.5);
        $curiosity = (float)($actorStats['curiosity'] ?? 0.5);
        return [
            'confirmation_bias' => round($this->clamp($dogmatism * 0.6 + (1 - $curiosity) * 0.3), 4),
            'loss_aversion'      => round($this->clamp((float)($actorStats['fear'] ?? 0.4) * 0.7), 4),
            'status_quo_bias'    => round($this->clamp($dogmatism * 0.5), 4),
            'authority_bias'     => round($this->clamp($dogmatism * 0.6), 4),
        ];
    }

    protected function getActorTraitAverages(int $universeId): array
    {
        $result = DB::table('actors')
            ->where('universe_id', $universeId)
            ->where('is_alive', true)
            ->selectRaw("
                AVG(CAST(traits->>8 AS NUMERIC))  as curiosity,
                AVG(CAST(traits->>9 AS NUMERIC))  as dogmatism,
                AVG(CAST(traits->>11 AS NUMERIC)) as fear
            ")
            ->first();

        return [
            'curiosity' => (float) (data_get($result, 'curiosity') ?? 0.5),
            'dogmatism' => (float) (data_get($result, 'dogmatism') ?? 0.5),
            'fear'      => (float) (data_get($result, 'fear') ?? 0.4),
        ];
    }

    protected function clamp(float $value): float
    {
        return max(0.0, min(1.0, round($value, 4)));
    }
}

