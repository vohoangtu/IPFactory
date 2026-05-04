<?php

namespace App\Modules\Intelligence\Services;

/**
 * Translates a scored action map into a selected action string.
 * Two modes: greedy (argmax) for production, softmax for Arena exploration.
 */
class ActionSelector
{
    /**
     * Deterministic: pick the highest-scoring action above the threshold.
     *
     * @param  array<string, float> $scores    action => utility
     * @param  array<string, float> $thresholds action => minimum threshold
     * @return string|null
     */
    public function greedy(array $scores, array $thresholds): ?string
    {
        arsort($scores);

        foreach ($scores as $action => $score) {
            $threshold = $thresholds[$action] ?? 1.0;
            if ($score >= $threshold) {
                return $action;
            }
        }

        return null;
    }

    /**
     * Stochastic: softmax sampling weighted by score.
     * Used during Arena training for better exploration of action space.
     *
     * @param  array<string, float> $scores
     * @param  float                $temperature  > 1 = more random, < 1 = greedier
     * @return string|null
     */
    public function softmax(array $scores, float $temperature = 1.0): ?string
    {
        if (empty($scores)) {
            return null;
        }

        // Apply temperature
        $scaled = array_map(fn($s) => $s / max($temperature, 0.01), $scores);

        // Numeric stability: subtract max
        $max = max($scaled);
        $exps = array_map(fn($s) => exp($s - $max), $scaled);

        $sum = array_sum($exps);
        if ($sum <= 0) {
            return null;
        }

        $probs = array_map(fn($e) => $e / $sum, $exps);

        // Sample
        $rand = mt_rand(0, PHP_INT_MAX) / PHP_INT_MAX;
        $cumulative = 0.0;
        foreach ($probs as $action => $prob) {
            $cumulative += $prob;
            if ($rand <= $cumulative) {
                return $action;
            }
        }

        return array_key_last($probs);
    }
}
