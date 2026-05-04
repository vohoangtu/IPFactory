<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Models\AgentDecision;
use App\Modules\Intelligence\Domain\Policy\FitnessScore;
use App\Modules\Intelligence\Entities\ActorEntity;
use App\Modules\Intelligence\Contracts\ActorRepositoryInterface;

/**
 * Computes civilizational fitness for a completed universe simulation.
 * Pure computation — reads DB snapshots, returns FitnessScore Value Object.
 */
class FitnessEvaluator
{
    public function __construct(
        private readonly ActorRepositoryInterface $actorRepository,
    ) {}

    public function compute(Universe $universe): FitnessScore
    {
        $actors = $this->actorRepository->findByUniverse($universe->id);

        if (empty($actors)) {
            return FitnessScore::extinction();
        }

        $alive      = array_filter($actors, fn($a) => $a->isAlive);
        $aliveCount = count($alive);
        $total      = count($actors);

        if ($aliveCount === 0) {
            return FitnessScore::extinction();
        }

        $survival  = $this->computeSurvivalScore($alive, $total);
        $stability = $this->computeStabilityScore($universe);
        $diversity = $this->computeDiversityScore($alive, $universe);
        $complexity = 0.0; // Populated by PolicyMutator — not applicable here

        return new FitnessScore($survival, $stability, $diversity, $complexity);
    }

    // ── Survival ────────────────────────────────────────────────────────────

    /**
     * Ratio of alive actors at end-tick, weighted by mean generation reached.
     */
    private function computeSurvivalScore(array $alive, int $total): float
    {
        if ($total === 0) return 0.0;

        $survivalRatio   = count($alive) / $total;
        $meanGeneration  = array_sum(array_map(fn($a) => $a->generation, $alive)) / count($alive);
        $generationBonus = min(1.0, ($meanGeneration - 1) / 10); // max bonus at gen 11+

        return round(min(1.0, $survivalRatio * 0.7 + $generationBonus * 0.3), 6);
    }

    // ── Stability ───────────────────────────────────────────────────────────

    /**
     * Mean stability_index minus volatility — from state_vector.
     * Simple approach: reads last known vector. Not time-series yet.
     */
    private function computeStabilityScore(Universe $universe): float
    {
        $vec       = $universe->state_vector ?? [];
        $stability = (float) ($vec['stability_index'] ?? 0.5);
        $entropy   = (float) ($vec['entropy'] ?? 0.5);

        // Penalise both extremes: frozen (entropy < 0.05) and chaotic (entropy > 0.85)
        $stagnationPenalty = $entropy < 0.05 ? 0.15 : 0.0;
        $chaosPenalty      = $entropy > 0.85 ? ($entropy - 0.85) * 2 : 0.0;

        return round(max(0.0, $stability - $stagnationPenalty - $chaosPenalty), 6);
    }

    // ── Diversity ───────────────────────────────────────────────────────────

    /**
     * Variance across trait mean vectors + action distribution entropy.
     * Returns 0 when fewer than 3 actors (can't compute meaningful variance).
     */
    private function computeDiversityScore(array $actors, Universe $universe): float
    {
        if (count($actors) < 3) {
            return 0.0;
        }

        // Trait variance
        $traitDim  = count(\App\Modules\Intelligence\Entities\ActorEntity::TRAIT_DIMENSIONS);
        $variance  = 0.0;
        for ($d = 0; $d < $traitDim; $d++) {
            $vals     = array_map(fn($a) => (float) ($a->traits[$d] ?? 0.5), $actors);
            $mean     = array_sum($vals) / count($vals);
            $variance += array_sum(array_map(fn($v) => ($v - $mean) ** 2, $vals)) / count($vals);
        }
        $traitVariance = $variance / $traitDim;

        // Action distribution entropy (Shannon)
        $actions = AgentDecision::where('universe_id', $universe->id)
            ->selectRaw('action_type, count(*) as cnt')
            ->groupBy('action_type')
            ->pluck('cnt', 'action_type')
            ->toArray();

        $actionEntropy = $this->shannonEntropy($actions);

        return round(min(1.0, $traitVariance * 0.6 + $actionEntropy * 0.4), 6);
    }

    private function shannonEntropy(array $counts): float
    {
        $total = array_sum($counts);
        if ($total === 0) return 0.0;

        $entropy = 0.0;
        foreach ($counts as $c) {
            $p = $c / $total;
            if ($p > 0) {
                $entropy -= $p * log($p, 2);
            }
        }

        // Normalise to [0,1] by dividing by log2(N)
        $maxEntropy = log(max(1, count($counts)), 2);
        return $maxEntropy > 0 ? $entropy / $maxEntropy : 0.0;
    }
}

