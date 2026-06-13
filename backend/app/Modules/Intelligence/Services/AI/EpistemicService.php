<?php

namespace App\Modules\Intelligence\Services\AI;

use App\Modules\World\Models\Universe;

/**
 * Epistemic Service: Implements the "Huyền Nguyên" (Obscure Origin) philosophy.
 * Calculates knowledge noise and distorts data to simulate epistemic instability.
 */
class EpistemicService
{
    /**
     * Calculate Knowledge Noise (N_k) based on entropy and universe state.
     * Scale: 0.0 (Perfect Clarity) to 1.0 (Absolute Obscurity).
     * Implements "Self-Decaying Truth": Noise increases with time and entropy autonomously.
     */
    public function calculateNoise(Universe $universe, float $entropy): float
    {
        // 1. Entropy-driven Decay (T1)
        $baseNoise = $entropy * 0.4; 

        // 2. Temporal Decay (T8 - Hữu hạn): Càng xa điểm Genesis, dữ liệu càng mờ
        $tick = $universe->current_tick;
        $temporalNoise = min(0.3, ($tick / 100000) * 0.1); 

        // 3. Active crises increase noise (Local Emergence)
        $vec = $universe->state_vector ?? [];
        $activeCrisesCount = count($vec['active_crises'] ?? []);
        $crisisBoost = $activeCrisesCount * 0.1;

        // 4. Structural Instability (SCI penalty)
        $sci = $universe->snapshots()->latest()->first()?->metrics['ip_score'] ?? 0.5;
        $sciPenalty = (1.0 - $sci) * 0.2;

        return min(1.0, max(0.0, $baseNoise + $temporalNoise + $crisisBoost + $sciPenalty));
    }

    /**
     * Distort a data vector based on the current noise level.
     * Uses Deterministic PRNG from Universe Seed (Self-Regulating Reality).
     */
    public function distort(Universe $universe, array $data, float $noise): array
    {
        if ($noise <= 0.02) {
            return $data; // Perfect clarity threshold
        }

        // Initialize deterministic random for this specific universe state
        $prng = \App\Support\Simulation\SimulationPRNG::forUniverse($universe);
        
        $distorted = [];
        foreach ($data as $key => $value) {
            if (is_numeric($value)) {
                // Structured Noise: Consistent distortion for the same state
                $offset = ($prng->nextFloat() * 2 - 1) * $noise;
                $distorted[$key] = max(0.0, min(1.0, (float)$value + $offset));
            } elseif (is_array($value)) {
                $distorted[$key] = $this->distort($universe, $value, $noise);
            } else {
                // String distortion: Obscure parts of strings if noise is high
                if ($noise > 0.6 && strlen($value) > 10) {
                    $distorted[$key] = $this->obscureText($value, $noise, $prng);
                } else {
                    $distorted[$key] = $value;
                }
            }
        }

        return $distorted;
    }

    /**
     * Obscure text segments based on noise level.
     */
    protected function obscureText(string $text, float $noise, $prng): string
    {
        $words = explode(' ', $text);
        foreach ($words as &$word) {
            if ($prng->nextFloat() < ($noise - 0.4)) {
                $word = str_repeat('…', min(3, strlen($word)));
            }
        }
        return implode(' ', $words);
    }

    /**
     * Calculate Reality Stability (S_r) - The inversely proportional measure to noise.
     * High Stability = Predictable laws. Low Stability = Axiom drift.
     */
    public function calculateStability(Universe $universe): float
    {
        $latest = $universe->snapshots()->orderByDesc('tick')->first();
        $metrics = $latest?->metrics ?? [];
        
        $sci = $metrics['ip_score'] ?? 0.5;
        $gradient = $metrics['instability_gradient'] ?? 0.0;
        $entropy = $latest?->entropy ?? 0.5;

        // Stability is destroyed by entropy and steep gradients
        $stability = ($sci * 0.6) + ((1.0 - $entropy) * 0.2) + ((1.0 - $gradient) * 0.2);
        
        return max(0.0, min(1.0, $stability));
    }

    /**
     * Get clarity label based on noise level (§2 of Theory).
     */
    public function getClarityLabel(float $noise): string
    {
        if ($noise < 0.2) return 'Chân Thực (Canonical)';
        if ($noise < 0.5) return 'Mơ Hồ (Obscure)';
        if ($noise < 0.8) return 'Huyền Sử (Mythic)';
        return 'Hư Vô (Void Echo)';
    }

    /**
     * Get the qualitative state of existence.
     */
    public function getExistenceState(float $noise): array
    {
        return match (true) {
            $noise < 0.2 => [
                'tier' => 'I',
                'name' => 'Chân Thực',
                'description' => 'Quy luật vật lý nhất quán. Dữ liệu chính xác tuyệt đối.',
                'effect' => 'Deterministic execution.'
            ],
            $noise < 0.5 => [
                'tier' => 'II',
                'name' => 'Mơ Hồ',
                'description' => 'Hằng số bắt đầu biến động. Thực tại bị nhòe ở các biên.',
                'effect' => 'Minor axiom drift.'
            ],
            $noise < 0.8 => [
                'tier' => 'III',
                'name' => 'Huyền Sử',
                'description' => 'Lịch sử bị biến dạng thành biểu tượng. Các Agent trở thành Icon.',
                'effect' => 'Narrative weight > Physical weight.'
            ],
            default => [
                'tier' => 'IV',
                'name' => 'Hư Vô',
                'description' => 'Sự tồn tại tan rã. Không gian mất liên kết topo.',
                'effect' => 'Structural collapse.'
            ]
        };
    }
}


