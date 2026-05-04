<?php

namespace App\Modules\Intelligence\Services\Analysis;

/**
 * Analyzes sequences of regime winners to detect macro-cycle attractors.
 * (e.g., detects if the civilization is stuck in Warlord -> Technocrat -> RogueAI loop)
 */
class RegimeSequenceAnalyzer
{
    /**
     * Analyze a sequence of winner archetype IDs to find periodic patterns.
     *
     * @param array $sequence List of archetype IDs.
     * @return array{is_periodic: bool, period: int, pattern: array, stability: float}
     */
    public function analyze(array $sequence): array
    {
        $n = count($sequence);
        if ($n < 4) {
            return [
                'is_periodic' => false,
                'period' => 0,
                'pattern' => [],
                'stability' => 0.0,
            ];
        }

        // Try candidate periods from 1 up to n/2
        for ($period = 1; $period <= floor($n / 2); $period++) {
            $isMatch = true;
            $pattern = array_slice($sequence, 0, $period);
            
            // Check if the rest of the sequence matches the pattern
            for ($i = $period; $i < $n; $i++) {
                if ($sequence[$i] !== $pattern[$i % $period]) {
                    $isMatch = false;
                    break;
                }
            }

            if ($isMatch) {
                // Return immediately for exact periodic match
                return [
                    'is_periodic' => true,
                    'period' => $period,
                    'pattern' => $pattern,
                    'stability' => 1.0,
                ];
            }
        }

        // If no exact match, try fuzzy matching (allowing some noise)
        return $this->detectFuzzyCycle($sequence);
    }

    /**
     * Detects cycles that are roughly periodic but contain noise or regime shifts.
     */
    private function detectFuzzyCycle(array $sequence): array
    {
        $n = count($sequence);
        $bestPeriod = 0;
        $bestStability = 0.0;
        $bestPattern = [];

        for ($period = 2; $period <= floor($n / 3); $period++) {
            $matches = 0;
            $patternCandidates = [];

            // Count occurrences of each element at each position in cycle
            for ($i = 0; $i < $period; $i++) {
                $counts = [];
                for ($j = $i; $j < $n; $j += $period) {
                    $val = $sequence[$j];
                    $counts[$val] = ($counts[$val] ?? 0) + 1;
                }
                arsort($counts);
                $winner = key($counts);
                $patternCandidates[] = $winner;
                $matches += $counts[$winner];
            }

            $stability = $matches / $n;
            if ($stability > $bestStability && $stability > 0.70) {
                $bestStability = $stability;
                $bestPeriod = $period;
                $bestPattern = $patternCandidates;
            }
        }

        return [
            'is_periodic' => $bestStability > 0.70,
            'period' => $bestPeriod,
            'pattern' => $bestPattern,
            'stability' => $bestStability,
        ];
    }

    /**
     * Get the distribution of winner dominance.
     */
    public function getDominanceDistribution(array $sequence): array
    {
        $distribution = array_count_values($sequence);
        arsort($distribution);
        
        $total = count($sequence);
        if ($total === 0) return [];

        return array_map(fn($count) => $count / $total, $distribution);
    }
}
