<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Domain\Phase\PhaseDetector;

class MacroStateEvolution
{
    public function __construct(
        protected PhaseDetector $phaseDetector
    ) {}

    public function evolve(
        Universe $universe,
        array $archetypeRatios, // Tỷ lệ archetypes hiện tại
        float $polarizationIndex,
        float $rngNoise = 0.0,
        float $culturalMomentum = 0.0
    ): Universe {
        // 1. Khởi tạo MacroPressure từ tỷ lệ archetypes
        $pressure = \App\Modules\Intelligence\Domain\Macro\MacroPressure::fromRatios($archetypeRatios, $polarizationIndex);

        // 2. Tính toán các thay đổi (deltas)
        $currentEntropy = (float)($universe->entropy ?? 0.5);
        $currentTechLevel = (float)($universe->level ?? 1);
        
        $deltas = $pressure->computeDeltas($currentEntropy, $currentTechLevel);

        // 3. Áp dụng deltas vào Universe
        $entropy = $currentEntropy + $deltas['entropy_delta'] + ($rngNoise * 0.01);
        $techDelta = $deltas['tech_delta'];

        // Phase 27: Hysteresis (Path Dependence)
        $flags = $universe->state_vector['historical_flags'] ?? [];
        if (!empty($flags['industrialized']) && $techDelta < 0) {
            // Khó quay lại thời tiền công nghiệp một khi đã đạt được (Damping regression)
            $techDelta *= 0.2; 
        }

        $techLevel = $currentTechLevel + $techDelta;
        
        // Hysteresis Floor: Tech level can't drop below 3 if industrialized
        if (!empty($flags['industrialized'])) {
            $techLevel = max(3.0, $techLevel);
        }

        $stability = (float)($universe->structural_coherence ?? 0.5);
        $stability += $deltas['stability_delta'];
        
        // Damping stability towards 0.5 default if no active pressure
        $stability += (0.5 - $stability) * 0.02;

        // Soft limit
        $universe->entropy = max(0.0, min(1.0, $entropy));
        $universe->structural_coherence = max(0.0, min(1.0, $stability));
        $universe->level = (int) round(max(1, $techLevel));
        
        // 4. Cập nhật Phase và Flags
        $phaseScore = $this->phaseDetector->detect($universe->entropy, $polarizationIndex, $universe->level, $culturalMomentum, $flags);
        
        // Store phase score in state_vector or metrics if needed
        $stateVector = $universe->state_vector ?? [];
        $stateVector['phase_score'] = [
            'primitive' => $phaseScore->primitive,
            'feudal' => $phaseScore->feudal,
            'industrial' => $phaseScore->industrial,
            'information' => $phaseScore->information,
            'fragmented' => $phaseScore->fragmented,
        ];
        $universe->state_vector = $stateVector;

        // Phase 33: Globalized damping
        if (!empty($flags['globalized'])) {
            // Globalization dampening polarization
            $polarizationIndex *= 0.5; 
        }

        $this->updateHistoricalFlags($universe, $phaseScore);

        return $universe;
    }
    
    /**
     * Concept III: Historical Flags
     */
    private function updateHistoricalFlags(Universe $universe, \App\Modules\Intelligence\Domain\Phase\PhaseScore $phaseScore): void
    {
        $stateVector = $universe->state_vector ?? [];
        if (!isset($stateVector['historical_flags'])) {
            $stateVector['historical_flags'] = [];
        }

        $flags = &$stateVector['historical_flags'];
        $techLevel = $universe->level ?? 1;

        if ($techLevel >= 3) {
            $flags['industrialized'] = true;
        }
        
        if ($techLevel >= 7 || $phaseScore->information > 0.5) {
            $flags['information_age'] = true;
        }

        if ($phaseScore->information > 0.6 && ($universe->structural_coherence ?? 0.5) > 0.5) {
            $flags['globalized'] = true;
        }
        
        $flags['peak_tech_level'] = max($flags['peak_tech_level'] ?? 1, $techLevel);

        if (($universe->entropy ?? 0.5) > 0.8) {
            $flags['collapsed_once'] = true;
        }

        $universe->state_vector = $stateVector;
    }
}

