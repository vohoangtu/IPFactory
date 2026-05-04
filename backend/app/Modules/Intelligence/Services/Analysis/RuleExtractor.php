<?php

namespace App\Modules\Intelligence\Services\Analysis;

/**
 * Extracts human-readable (and machine-executable) rules from discovered patterns.
 * (e.g., translates "regime_precursor" into an Attractor Activation Rule)
 */
class RuleExtractor
{
    /**
     * Create executable rules for CivilizationAttractors.
     *
     * @param array $patterns Output from PatternAnalyzer.
     * @return array List of rule objects for the DB.
     */
    public function extractRules(array $patterns): array
    {
        $rules = [];
        foreach ($patterns as $p) {
            if ($p['type'] === 'regime_precursor') {
                $rules[] = [
                    'name' => "Precursor for " . $p['archetype'],
                    'activation_rules' => [
                        // Example: stability < 0.3 AND coercion > 0.6
                        ['key' => 'stability', 'op' => '<', 'value' => $p['precursor_state']['stability'] ?? 0.5],
                        ['key' => 'coercion', 'op' => '>', 'value' => $p['precursor_state']['coercion'] ?? 0.5],
                    ],
                    'force_map' => [
                        $p['archetype'] => 1.5, // 50% boost to this archetype
                    ]
                ];
            }
        }
        return $rules;
    }
}
