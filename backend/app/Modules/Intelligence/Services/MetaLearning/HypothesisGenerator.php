<?php

namespace App\Modules\Intelligence\Services\MetaLearning;

/**
 * Generates theoretical hypotheses about civilization dynamics.
 * Bridges numerical meta-learning with architectural theory.
 */
class HypothesisGenerator
{
    /**
     * Generate hypotheses based on optimized parameters.
     */
    public function generate(ParameterGenome $optimized, ParameterGenome $original): array
    {
        $hypotheses = [];
        
        $diff = $this->calculateDiff($optimized->worldConfig, $original->worldConfig);
        
        foreach ($diff as $key => $delta) {
            if (abs($delta) > 0.05) {
                $direction = $delta > 0 ? "Increasing" : "Decreasing";
                $hypotheses[] = [
                    'key' => $key,
                    'statement' => "$direction $key leads to higher emergent complexity.",
                    'confidence' => abs($delta) * 10,
                ];
            }
        }

        return $hypotheses;
    }

    private function calculateDiff(array $a, array $b): array
    {
        $diff = [];
        foreach ($a as $k => $v) {
            if (is_numeric($v) && isset($b[$k])) {
                $diff[$k] = $v - $b[$k];
            }
        }
        return $diff;
    }
}
