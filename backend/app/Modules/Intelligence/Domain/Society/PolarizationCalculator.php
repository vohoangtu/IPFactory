<?php

namespace App\Modules\Intelligence\Domain\Society;

use App\Modules\Intelligence\Entities\ActorState;

/**
 * PolarizationCalculator — Tính chỉ số phân cực xã hội.
 * 
 * Theo V22 Masterplan §2:
 * - polarization_index = stddev(Dominance + Vengeance across actors)
 * - social_cohesion = avg(Solidarity+Conformity) × (1 - polarization)
 * - cultural_momentum = moving_average(Δphase_score, window=5) — computed externally
 */
final class PolarizationCalculator
{
    /**
     * Tính toán các chỉ số xã hội từ danh sách actors.
     *
     * @param array<ActorState> $actors
     * @return array{polarization_index: float, social_cohesion: float, aggression_mean: float}
     */
    public function calculate(array $actors): array
    {
        if (empty($actors)) {
            return [
                'polarization_index' => 0.0,
                'social_cohesion' => 1.0,
                'aggression_mean' => 0.0,
            ];
        }

        $count = count($actors);

        // 1. Aggression scores: (Dominance + Vengeance) / 2 per actor
        $aggressionScores = [];
        $solidaritySum = 0.0;
        $conformitySum = 0.0;

        foreach ($actors as $actor) {
            $dominance = $actor->traits['Dominance'] ?? 0.0;
            $vengeance = $actor->traits['Vengeance'] ?? 0.0;
            $aggressionScores[] = ($dominance + $vengeance) / 2.0;

            $solidaritySum += $actor->traits['Solidarity'] ?? 0.0;
            $conformitySum += $actor->traits['Conformity'] ?? 0.0;
        }

        // 2. Polarization = stddev of aggression scores
        $aggressionMean = array_sum($aggressionScores) / $count;
        $varianceSum = 0.0;
        foreach ($aggressionScores as $score) {
            $varianceSum += ($score - $aggressionMean) ** 2;
        }
        $polarization = sqrt($varianceSum / $count);

        // Normalize to [0, 1] — max stddev of values in [0, 1] is 0.5
        $polarization = min(1.0, $polarization / 0.5);

        // 3. Social Cohesion = avg(Solidarity+Conformity) × (1 - polarization)
        $avgSolidarityConformity = ($solidaritySum + $conformitySum) / (2.0 * $count);
        $socialCohesion = $avgSolidarityConformity * (1.0 - $polarization);

        return [
            'polarization_index' => round($polarization, 6),
            'social_cohesion' => round(max(0.0, min(1.0, $socialCohesion)), 6),
            'aggression_mean' => round($aggressionMean, 6),
        ];
    }
}
