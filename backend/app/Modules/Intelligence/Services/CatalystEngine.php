<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;
use App\Support\Simulation\SimulationPRNG;
use Illuminate\Support\Facades\Log;

/**
 * Phase 45: Civilization Catalyst Engine (Sự gián đoạn phi tuyến)
 * 
 * Mô phỏng các sự kiện chất xúc tác làm bẻ hướng lịch sử dựa trên áp lực xã hội.
 */
class CatalystEngine
{
    private SimulationPRNG $prng;

    public function __construct(SimulationPRNG $prng)
    {
        $this->prng = $prng;
    }

    /**
     * Kiểm tra và kích hoạt các sự kiện chất xúc tác.
     */
    public function evaluateCatalysts(Universe $universe, array $fields, float $entropy): ?array
    {
        $stateVector = $universe->state_vector;
        $activeCatalysts = $stateVector['active_catalysts'] ?? [];
        $metrics = $stateVector['catalyst_metrics'] ?? ['systemic_pressure' => 0.0];
        
        // Dọn dẹp các catalyst đã hết hạn
        foreach ($activeCatalysts as $id => $data) {
            if ($universe->current_tick > ($data['end_tick'] ?? 0)) {
                unset($activeCatalysts[$id]);
                Log::info("CATALYST: Event '{$id}' has stabilized in Universe #{$universe->id}");
            }
        }

        // Phase 45: Calculate Systemic Pressure based on field imbalance (Standard Deviation)
        $fieldValues = array_values($fields);
        $metrics['systemic_pressure'] = $this->calculateStandardDeviation($fieldValues);

        // 1. Scientific Revolution (Prerequisite for Industrial)
        if (!isset($activeCatalysts['scientific_revolution']) && !isset($stateVector['historical_catalysts']['scientific_revolution'])) {
            $kPressure = $fields['knowledge'] ?? 0;
            if ($kPressure > 0.8 && $metrics['systemic_pressure'] > 0.15 && $this->prng->nextFloat() < 0.05) {
                $activeCatalysts['scientific_revolution'] = [
                    'start_tick' => $universe->current_tick,
                    'end_tick'   => $universe->current_tick + 120,
                    'multipliers' => ['knowledge' => 2.5, 'innovation' => 1.5]
                ];
                $stateVector['historical_catalysts']['scientific_revolution'] = $universe->current_tick;
                Log::warning("CATALYST EVENT: Scientific Revolution triggered!");
            }
        }

        // 2. Industrial Revolution (Chain: requires Scientific Revolution history)
        if (!isset($activeCatalysts['industrial_revolution']) && isset($stateVector['historical_catalysts']['scientific_revolution'])) {
            $wPressure = $fields['wealth'] ?? 0;
            if ($wPressure > 0.7 && $this->prng->nextFloat() < 0.04) {
                $activeCatalysts['industrial_revolution'] = [
                    'start_tick' => $universe->current_tick,
                    'end_tick'   => $universe->current_tick + 200,
                    'multipliers' => ['wealth' => 3.0, 'production' => 3.0, 'survival' => 0.8] // Growth at cost of survival entropy
                ];
                Log::warning("CATALYST EVENT: Industrial Revolution triggered by previous scientific foundation!");
            }
        }

        // 3. Dark Age Spiral (Crisis Catalyst)
        if (!isset($activeCatalysts['dark_age_spiral'])) {
            $stability = $universe->structural_coherence ?? 0.5;
            if ($stability < 0.25 && $entropy > 0.8 && $this->prng->nextFloat() < 0.02) {
                $activeCatalysts['dark_age_spiral'] = [
                    'start_tick' => $universe->current_tick,
                    'end_tick'   => $universe->current_tick + 300,
                    'multipliers' => ['wealth' => 0.4, 'knowledge' => 0.5, 'power' => 0.6]
                ];
                Log::error("CATALYST DISRUPTION: Civilization has entered a DARK AGE SPIRAL.");
            }
        }

        // 4. Ideological Warfare
        $dominantIdeology = $stateVector['dominant_ideology'] ?? null;
        if ($dominantIdeology && ($metrics['systemic_pressure'] > 0.25) && !isset($activeCatalysts['ideological_warfare'])) {
             if ($this->prng->nextFloat() < 0.03) {
                 $activeCatalysts['ideological_warfare'] = [
                    'start_tick' => $universe->current_tick,
                    'end_tick'   => $universe->current_tick + 100,
                    'multipliers' => ['power' => 1.5, 'meaning' => 1.5, 'wealth' => 0.7, 'status' => 2.0]
                ];
                Log::info("CATALYST: Ideological Warfare erupted due to extreme systemic pressure.");
             }
        }

        $stateVector['active_catalysts'] = $activeCatalysts;
        $stateVector['catalyst_metrics'] = $metrics;
        $universe->state_vector = $stateVector;

        return $activeCatalysts;
    }

    private function calculateStandardDeviation(array $values): float
    {
        $n = count($values);
        if ($n <= 1) return 0.0;
        $mean = array_sum($values) / $n;
        $sqResid = 0.0;
        foreach ($values as $v) {
            $sqResid += ($v - $mean) ** 2;
        }
        return sqrt($sqResid / ($n - 1));
    }

    /**
     * Áp dụng tác động của các catalyst lên trường lực.
     */
    public function applyAmplification(array &$fields, array $activeCatalysts): void
    {
        foreach ($activeCatalysts as $id => $data) {
            $multipliers = $data['multipliers'] ?? [];
            foreach ($multipliers as $field => $mult) {
                if (isset($fields[$field])) {
                    $fields[$field] *= $mult;
                }
            }
        }
    }
}


