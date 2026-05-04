<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;

/**
 * Phase 27: Diplomatic Engine.
 * Calculates tension between factions and updates diplomatic states.
 */
class DiplomaticEngine
{
    public const STATE_PEACE = 'peace';
    public const STATE_TENSION = 'tension';
    public const STATE_WAR = 'war';

    /**
     * Tension(FactionA, FactionB) = distance(ideology_vector) * entropy_mod
     */
    public function updateDiplomacy(Universe $universe): void
    {
        $stateVector = $universe->state_vector ?? [];
        $factions = $stateVector['factions'] ?? [];
        if (count($factions) < 2) return;

        $diplomacy = $stateVector['diplomacy'] ?? [];
        $entropy = (float)($universe->entropy ?? 0.5);

        for ($i = 0; $i < count($factions); $i++) {
            for ($j = $i + 1; $j < count($factions); $j++) {
                $fA = $factions[$i];
                $fB = $factions[$j];
                
                $idA = $fA['id'];
                $idB = $fB['id'];
                $key = $idA < $idB ? "{$idA}_{$idB}" : "{$idB}_{$idA}";

                $currentTension = $diplomacy[$key]['tension'] ?? 0.0;
                
                // Calculate target tension based on ideology distance
                $dist = $this->calculateIdeologyDistance(
                    $fA['ideology_vector'] ?? [0.5, 0.5, 0.5],
                    $fB['ideology_vector'] ?? [0.5, 0.5, 0.5]
                );

                // Entropy increases friction/tension
                $targetTension = $dist * (1.0 + $entropy);
                
                // Smoothing (Hysteresis-like smoothing)
                $newTension = $currentTension + ($targetTension - $currentTension) * 0.1;

                $state = self::STATE_PEACE;
                if ($newTension > 0.7) $state = self::STATE_WAR;
                elseif ($newTension > 0.4) $state = self::STATE_TENSION;

                $diplomacy[$key] = [
                    'tension' => $newTension,
                    'state' => $state,
                    'last_updated_tick' => $universe->state_vector['tick'] ?? 0
                ];
            }
        }

        $stateVector['diplomacy'] = $diplomacy;
        $universe->state_vector = $stateVector;
    }

    protected function calculateIdeologyDistance(array $v1, array $v2): float
    {
        // Euclidean distance in 3D (Dominance, Solidarity, Curiosity)
        $sum = 0;
        foreach ($v1 as $i => $val) {
            $sum += pow($val - ($v2[$i] ?? 0.5), 2);
        }
        return sqrt($sum);
    }
}

