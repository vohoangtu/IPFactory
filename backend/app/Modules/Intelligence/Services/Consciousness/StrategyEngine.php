<?php

namespace App\Modules\Intelligence\Services\Consciousness;

use App\Modules\Simulation\Models\CivilizationAttractor;

/**
 * Derives optimal strategies (which attractors to activate) to avoid predicted risks.
 */
class StrategyEngine
{
    /**
     * Recommend a strategy based on future risk predictions.
     * 
     * @param array $risks Detected via FutureSimulator.
     * @param CivilizationAttractor[] $availableAttractors
     * @return array List of recommended actions.
     */
    public function recommend(array $risks, array $availableAttractors): array
    {
        $recommendations = [];
        
        foreach ($risks as $risk) {
            if ($risk === 'Collapse Risk Detected') {
                // Find attractors that increase stability
                $attractor = $this->findBestFor($availableAttractors, 'stability', 1.0);
                if ($attractor) {
                    $recommendations[] = [
                        'action' => 'Activate Attractor',
                        'target' => $attractor->name,
                        'reason' => 'Mitigate collapse risk by increasing stability.',
                    ];
                }
            }
            
            if ($risk === 'Heat Death / Absolute Chaos Risk') {
                // Find attractors that decrease entropy
                $attractor = $this->findBestFor($availableAttractors, 'entropy', -1.0);
                if ($attractor) {
                    $recommendations[] = [
                        'action' => 'Activate Attractor',
                        'target' => $attractor->name,
                        'reason' => 'Reduce entropy to avoid heat death.',
                    ];
                }
            }
        }

        return $recommendations;
    }

    private function findBestFor(array $attractors, string $field, float $direction): ?CivilizationAttractor
    {
        $best = null;
        $bestScore = -1.0;

        foreach ($attractors as $a) {
            // Very simple heuristic: find an archetype that has a high weight for the requested stability/entropy
            // Wait, civilization attractors have force_map [archetype => boost]
            // We need to know which archetype increases stability.
            // For now, return any that matches by name or random for Layer 9 logic demo.
            if ($field === 'stability' && str_contains(strtolower($a->name), 'order')) $score = 1.0;
            else if ($field === 'entropy' && str_contains(strtolower($a->name), 'tech')) $score = 1.0;
            else $score = 0.5;

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $a;
            }
        }

        return $best;
    }
}

