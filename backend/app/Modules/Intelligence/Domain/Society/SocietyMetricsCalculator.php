<?php

namespace App\Modules\Intelligence\Domain\Society;

use App\Modules\Intelligence\Entities\ActorState;

class SocietyMetricsCalculator
{
    /**
     * polarization_index = stddev(Dominance + Vengeance across actors)
     * 
     * @param array<ActorState> $actors
     * @return float
     */
    public function calculatePolarization(array $actors): float
    {
        if (count($actors) < 2) {
            return 0.0;
        }

        $dimensions = [
            'survival', 'reproduction', 'wealth', 'power', 
            'knowledge', 'meaning', 'status', 'belonging'
        ];

        $totalVariance = 0.0;

        foreach ($dimensions as $dim) {
            $values = [];
            $sum = 0.0;

            foreach ($actors as $actor) {
                $val = $this->getDimensionValue($actor, $dim);
                $values[] = $val;
                $sum += $val;
            }

            $mean = $sum / count($values);
            $varianceSum = 0.0;
            foreach ($values as $val) {
                $varianceSum += pow($val - $mean, 2);
            }
            $totalVariance += ($varianceSum / count($values));
        }

        // Return average standard deviation across all dimensions
        return sqrt($totalVariance / count($dimensions));
    }

    private function getDimensionValue(ActorState $actor, string $dim): float
    {
        $traits = $actor->traits;
        return match($dim) {
            'survival'     => ($traits['Resilience'] ?? 0.5),
            'reproduction' => ($traits['Vitality'] ?? 0.5),
            'wealth'       => ($traits['Pragmatism'] ?? 0.5) * 0.7 + ($traits['Ambition'] ?? 0.5) * 0.3,
            'power'        => ($traits['Dominance'] ?? 0.5) * 0.6 + ($traits['Coercion'] ?? 0.5) * 0.4,
            'knowledge'    => ($traits['Curiosity'] ?? 0.5),
            'meaning'      => ($traits['Hope'] ?? 0.5) * 0.7 + (1 - ($traits['Dogmatism'] ?? 0.5)) * 0.3,
            'status'       => ($traits['Pride'] ?? 0.5) * 0.8 + ($traits['Dominance'] ?? 0.5) * 0.2,
            'belonging'    => ($traits['Solidarity'] ?? 0.5) * 0.4 + ($traits['Conformity'] ?? 0.5) * 0.3 + ($traits['Loyalty'] ?? 0.3),
            default        => 0.5
        };
    }

    /**
     * social_cohesion = avg(Solidarity + Conformity) × (1 - polarization)
     * 
     * @param array<ActorState> $actors
     * @param float $polarization
     * @return float
     */
    public function calculateSocialCohesion(array $actors, float $polarization): float
    {
        if (empty($actors)) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($actors as $actor) {
            $sum += (($actor->traits['Solidarity'] ?? 0.0) + ($actor->traits['Conformity'] ?? 0.0)) / 2;
        }

        $avg = $sum / count($actors);

        return $avg * (1 - $polarization);
    }

    /**
     * cultural_momentum = moving_average(Δphase_score, window=5)
     * 
     * @param array $historicalPhaseScores The stored PhaseScore arrays from recent Snapshots. 
     * @return float
     */
    public function calculateCulturalMomentum(array $historicalPhaseScores): float
    {
        $count = count($historicalPhaseScores);
        if ($count < 2) return 0.0;

        $deltas = [];
        for ($i = 1; $i < $count; $i++) {
            $prev = $historicalPhaseScores[$i - 1];
            $curr = $historicalPhaseScores[$i];

            // Simply sum the absolute differences of all phase parameters
            $diffSum = 
                abs(($curr['primitive'] ?? 0) - ($prev['primitive'] ?? 0)) +
                abs(($curr['feudal'] ?? 0) - ($prev['feudal'] ?? 0)) +
                abs(($curr['industrial'] ?? 0) - ($prev['industrial'] ?? 0)) +
                abs(($curr['information'] ?? 0) - ($prev['information'] ?? 0)) +
                abs(($curr['fragmented'] ?? 0) - ($prev['fragmented'] ?? 0));

            $deltas[] = $diffSum;
        }

        $avgDelta = array_sum($deltas) / count($deltas);

        return min(1.0, $avgDelta * 5); // Exaggerate slightly or apply a coefficient
    }
}
