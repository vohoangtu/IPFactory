<?php

namespace App\Modules\Psychology\Services;

use App\Modules\Psychology\ValueObjects\MemoryStream;

/**
 * MemoryInfluenceAnalyzer – distills MemoryStream into context variables
 * for use by ExpressionEngine in behavior scoring.
 *
 * Provides: trauma (Freudian hidden bias), social_momentum, recent_negativity
 */
final class MemoryInfluenceAnalyzer
{
    /**
     * Analyze memory and return extra context variables for DSL expressions.
     *
     * @return array<string, float>  Merged into state context for ExpressionEngine
     */
    public function analyze(MemoryStream $memory): array
    {
        $traumaScore    = $memory->traumaTotal();
        $recentBias     = $memory->recentBias(5);
        $socialScore    = $this->socialMomentum($memory);
        $recentNeg      = max(0.0, -$recentBias);

        return [
            'trauma'           => $traumaScore,
            'social_momentum'  => $socialScore,
            'recent_negativity'=> $recentNeg,
            'memory_valence'   => $memory->avgValence(),
        ];
    }

    /**
     * Sum of positive social memory weights (normalized).
     */
    private function socialMomentum(MemoryStream $memory): float
    {
        $socialItems = $memory->filterByType('social');
        if (empty($socialItems)) {
            return 0.0;
        }

        $positiveSum = 0.0;
        foreach ($socialItems as $item) {
            if ($item->valence > 0) {
                $positiveSum += $item->valence * $item->weight;
            }
        }
        return min(1.0, $positiveSum / max(1, count($socialItems)));
    }
}
