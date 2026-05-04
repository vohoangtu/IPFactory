<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Contracts\UniverseEvaluatorInterface;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * DecisionEngine – Possibility Space Navigator
 *
 * Original: simple fork/continue/archive decision.
 * Upgraded: computes novelty, complexity, and divergence scores to guide
 * which universes are worth exploring (forking) vs pruning.
 *
 * This implements the "Possibility Space Navigator" from Level 7 theory:
 *
 *   score = w1 * novelty + w2 * complexity + w3 * divergence
 *
 *   novelty    = how different this universe state is from known civilization archetypes
 *   complexity = how many active institutions + trade density + knowledge level
 *   divergence = how much this universe has drifted from its parent
 *
 * High score → fork: create branches to explore this interesting region
 * Low score  → archive or continue without branching
 *
 * When combined with CivilizationFieldEngine.detectArchetype(), this engine
 * provides "Autonomous Civilization Discovery": it logs novel archetypes
 * that emerge without being scripted.
 */
class DecisionEngine implements \App\Contracts\DecisionEngineInterface
{
    /** Score weights */
    const W_NOVELTY    = 0.50;
    const W_COMPLEXITY = 0.30;
    const W_DIVERGENCE = 0.20;

    /** Threshold above which forking is recommended */
    const FORK_THRESHOLD = 0.65;

    /** Threshold below which archiving is recommended */
    const ARCHIVE_THRESHOLD = 0.15;

    /** Minimum ticks before a universe can be archived (avoid archiving in early ticks when complexity is still low). */
    const MIN_TICKS_BEFORE_ARCHIVE = 150;

    /** Known civilization archetype signatures (phase-space reference points) */
    const KNOWN_ARCHETYPES = [
        'agrarian_empire'       => ['survival' => 0.8, 'power' => 0.7, 'wealth' => 0.5, 'knowledge' => 0.3, 'meaning' => 0.7],
        'merchant_republic'     => ['survival' => 0.5, 'power' => 0.5, 'wealth' => 0.9, 'knowledge' => 0.6, 'meaning' => 0.3],
        'scientific_civ'        => ['survival' => 0.5, 'power' => 0.4, 'wealth' => 0.6, 'knowledge' => 0.9, 'meaning' => 0.5],
        'theocracy'             => ['survival' => 0.6, 'power' => 0.7, 'wealth' => 0.3, 'knowledge' => 0.3, 'meaning' => 0.9],
        'tribal_confederation'  => ['survival' => 0.9, 'power' => 0.4, 'wealth' => 0.3, 'knowledge' => 0.2, 'meaning' => 0.5],
        'military_state'        => ['survival' => 0.7, 'power' => 0.9, 'wealth' => 0.5, 'knowledge' => 0.4, 'meaning' => 0.4],
        'trade_empire'          => ['survival' => 0.5, 'power' => 0.6, 'wealth' => 0.85, 'knowledge' => 0.5, 'meaning' => 0.4],
        'dark_age'              => ['survival' => 0.8, 'power' => 0.3, 'wealth' => 0.2, 'knowledge' => 0.2, 'meaning' => 0.6],
    ];

    public function __construct(
        protected UniverseEvaluatorInterface $evaluator
    ) {}

    /**
     * Decide action for a universe snapshot.
     * Extended with Possibility Navigator scoring.
     *
     * @return array{action: string, meta: array, navigator_score: float}
     */
    public function decide(UniverseSnapshot $snapshot): array
    {
        $result         = $this->evaluator->evaluate($snapshot);
        $recommendation = $result['recommendation'] ?? 'continue';

        // Compute navigator scores
        $navScore = $this->computeNavigatorScore($snapshot);

        // Override recommendation based on navigator score
        if ($navScore['total'] >= self::FORK_THRESHOLD) {
             // Only override archive if score is high interest (>= 0.70)
             $isAlreadyStrategic = in_array($recommendation, ['merge', 'promote'], true);
             if (!$isAlreadyStrategic && ($recommendation !== 'archive' || $navScore['total'] >= 0.70)) {
                 $recommendation = 'fork';
             }
        } elseif ($navScore['total'] <= self::ARCHIVE_THRESHOLD && ! in_array($recommendation, ['fork', 'mutate', 'merge', 'promote'], true)) {
            // Don't archive very early universes: at tick 1 complexity is ~0 so score is artificially low.
            $tick = (int) ($snapshot->tick ?? 0);
            $minTicks = (int) config('worldos.autonomic.min_ticks_before_archive', self::MIN_TICKS_BEFORE_ARCHIVE);
            $forkGracePeriod = (int) config('worldos.autonomic.fork_grace_period_ticks', 50);
            $universe = $snapshot->universe;
            $inGracePeriod = $universe && $universe->forked_at_tick !== null
                && ($tick - (int) $universe->forked_at_tick) < $forkGracePeriod;
            if ($tick >= $minTicks && !$inGracePeriod) {
                $recommendation = 'archive';
            }
        }

        return [
            'action'          => $recommendation,
            'navigator_score' => $navScore['total'],
            'meta'            => array_merge($result['meta'] ?? [], [
                'ip_score'           => $result['ip_score'] ?? 0,
                'mutation_suggestion' => $result['mutation_suggestion'] ?? null,
                'reason'             => "Entropy: " . ($snapshot->entropy ?? 'N/A'),
                'novelty'            => $navScore['novelty'],
                'complexity'         => $navScore['complexity'],
                'divergence'         => $navScore['divergence'],
                'detected_archetype' => $navScore['nearest_archetype'],
                'is_novel_archetype' => $navScore['is_novel'],
            ]),
        ];
    }

    /**
     * Compute novelty + complexity + divergence navigator score.
     */
    public function computeNavigatorScore(UniverseSnapshot $snapshot): array
    {
        $vec     = (array) ($snapshot->state_vector ?? []);
        $fields  = (array) ($vec['fields'] ?? []);

        $novelty   = $this->computeNovelty($fields);
        $complexity = $this->computeComplexity($snapshot);
        $divergence = $this->computeDivergence($snapshot);

        $total = self::W_NOVELTY * $novelty
               + self::W_COMPLEXITY * $complexity
               + self::W_DIVERGENCE * $divergence;

        $nearest = $this->findNearestArchetype($fields);

        return [
            'novelty'          => round($novelty, 4),
            'complexity'       => round($complexity, 4),
            'divergence'       => round($divergence, 4),
            'total'            => round(min(1.0, $total), 4),
            'nearest_archetype' => $nearest['name'],
            'archetype_distance' => $nearest['distance'],
            'is_novel'         => $nearest['distance'] > 0.35, // > 35% away from any known archetype
        ];
    }

    /**
     * Novelty = distance from the nearest known civilization archetype in 5D field space.
     */
    protected function computeNovelty(array $fields): float
    {
        if (empty($fields)) return 0.5;
        $nearest = $this->findNearestArchetype($fields);
        return min(1.0, $nearest['distance']);
    }

    /**
     * Find the known archetype closest to this universe's field vector.
     */
    protected function findNearestArchetype(array $fields): array
    {
        $fieldNames = ['survival', 'power', 'wealth', 'knowledge', 'meaning'];
        $bestDist   = PHP_FLOAT_MAX;
        $bestName   = 'unknown';

        foreach (self::KNOWN_ARCHETYPES as $name => $archetype) {
            $dist = 0.0;
            foreach ($fieldNames as $f) {
                $a = (float) ($fields[$f] ?? 0.5);
                $b = (float) ($archetype[$f] ?? 0.5);
                $dist += ($a - $b) ** 2;
            }
            $dist = sqrt($dist / count($fieldNames)); // Normalized Euclidean
            if ($dist < $bestDist) {
                $bestDist = $dist;
                $bestName = $name;
            }
        }

        return ['name' => $bestName, 'distance' => round($bestDist, 4)];
    }

    /**
     * Complexity = normalized measure of institutional richness + knowledge.
     */
    protected function computeComplexity(UniverseSnapshot $snapshot): float
    {
        $universeId      = $snapshot->universe_id;
        $vec             = (array) ($snapshot->state_vector ?? []);
        $institutionCount = DB::table('institutional_entities')
            ->where('universe_id', $universeId)
            ->whereNull('collapsed_at_tick')
            ->count();

        $knowledgeField = (float) ($vec['fields']['knowledge'] ?? 0.3);
        $wealthField    = (float) ($vec['fields']['wealth'] ?? 0.3);

        // 15 institutions = 1.0 baseline
        $instScore = min(1.0, $institutionCount / 15.0);

        return min(1.0, $instScore * 0.5 + $knowledgeField * 0.3 + $wealthField * 0.2);
    }

    /**
     * Divergence = how much this snapshot differs from parent universe.
     */
    protected function computeDivergence(UniverseSnapshot $snapshot): float
    {
        $universe = $snapshot->universe;
        if (!$universe || !$universe->parent_universe_id) {
            return 0.5; // No parent = base novelty
        }

        $parentSnap = UniverseSnapshot::where('universe_id', $universe->parent_universe_id)
            ->orderByDesc('tick')
            ->first();

        if (!$parentSnap) return 0.5;

        $fields1 = (array) (($snapshot->state_vector ?? [])['fields'] ?? []);
        $fields2 = (array) (($parentSnap->state_vector ?? [])['fields'] ?? []);

        $fieldNames = ['survival', 'power', 'wealth', 'knowledge', 'meaning'];
        $dist = 0.0;
        foreach ($fieldNames as $f) {
            $dist += abs((float)($fields1[$f] ?? 0.5) - (float)($fields2[$f] ?? 0.5));
        }

        return min(1.0, $dist / count($fieldNames));
    }
}

