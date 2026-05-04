<?php

namespace App\Modules\Intelligence\Domain\Phase;

use App\Modules\Intelligence\Domain\Phase\PhaseScore;

class FitnessLandscapeProvider
{
    /**
     * Calculates the archetype fitness multipliers based on the current phase.
     * 
     * @param PhaseScore $phase
     * @return array<string, float> Map of archetype base names to their multiplier.
     */
    public function getMultipliers(PhaseScore $phase): array
    {
        // Warrior thrives in fragmentation, suffers in information age
        $warriorMultiplier = 1.0 + ($phase->fragmented * 1.5) - ($phase->information * 0.5);

        // Scholar thrives in information age, suffers in fragmentation
        $scholarMultiplier = 1.0 + ($phase->information * 1.2) - ($phase->fragmented * 0.3);

        // Merchant thrives in industrial age
        $merchantMultiplier = 1.0 + ($phase->industrial * 1.0);

        // Warlord thrives in extreme feudalism and fragmentation
        $warlordMultiplier = 1.0 + ($phase->fragmented * 2.0) + ($phase->feudal * 0.5);

        // Rogue AI thrives purely in Information age + fragmentation
        $rogueAiMultiplier = 1.0 + ($phase->information * 2.0) * $phase->fragmented;

        return [
            'Chiến Binh' => max(0.1, $warriorMultiplier),
            'Học Giả' => max(0.1, $scholarMultiplier),
            'Thương Nhân' => max(0.1, $merchantMultiplier),
            'Warlord' => max(0.1, $warlordMultiplier),
            'Đặc Biệt' => max(0.1, $rogueAiMultiplier),
            // Default 1.0 for unspecified archetypes
        ];
    }
}
