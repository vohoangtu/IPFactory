<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Domain\Phase\PhaseScore;

/**
 * Phase 25: Society Metrics.
 * Calculates social cohesion and cultural momentum.
 */
class SocietyMetricsCalculator
{
    /**
     * social_cohesion = avg(Solidarity+Conformity) × (1 - polarization)
     */
    public function calculateCohesion(array $actors, float $polarizationIndex): float
    {
        if (empty($actors)) return 0.5;

        $sumTraits = 0;
        foreach ($actors as $actor) {
            $solidarity = (float)($actor->traits['Solidarity'] ?? 0.5);
            $conformity = (float)($actor->traits['Conformity'] ?? 0.5);
            $sumTraits += ($solidarity + $conformity) / 2;
        }

        $avgTraits = $sumTraits / count($actors);
        
        return $avgTraits * (1.0 - $polarizationIndex);
    }

    /**
     * cultural_momentum = moving_average(Δphase_score, window=5)
     */
    public function calculateMomentum(Universe $universe, PhaseScore $currentScore): float
    {
        $snapshots = $universe->snapshots()->orderByDesc('tick')->limit(5)->get();
        if ($snapshots->count() < 2) return 0.0;

        $totalDelta = 0;
        $prevScore = null;

        // Note: snapshots are desc, so we iterate and compare with the one before it (older)
        foreach ($snapshots as $snap) {
            $state = $snap->state_vector ?? [];
            $scores = $state['phase_score'] ?? null;
            
            if (!$scores) continue;

            $phaseScoreObj = new PhaseScore(
                $scores['primitive'] ?? 0,
                $scores['feudal'] ?? 0,
                $scores['industrial'] ?? 0,
                $scores['information'] ?? 0,
                $scores['fragmented'] ?? 0
            );

            if ($prevScore !== null) {
                // Calculate delta between current (more recent) and prev (older)
                $delta = $this->getPhaseDelta($prevScore, $phaseScoreObj);
                $totalDelta += $delta;
            }
            $prevScore = $phaseScoreObj;
        }

        return $totalDelta / max(1, $snapshots->count() - 1);
    }

    protected function getPhaseDelta(PhaseScore $recent, PhaseScore $older): float
    {
        // Simple Euclidean distance between scores or just sum of absolute diffs
        return abs($recent->primitive - $older->primitive) +
               abs($recent->feudal - $older->feudal) +
               abs($recent->industrial - $older->industrial) +
               abs($recent->information - $older->information) +
               abs($recent->fragmented - $older->fragmented);
    }
}

