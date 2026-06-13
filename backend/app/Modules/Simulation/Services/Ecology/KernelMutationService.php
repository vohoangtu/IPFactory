<?php

namespace App\Modules\Simulation\Services\Ecology;

use App\Modules\World\Models\Universe;
use App\Support\Simulation\SimulationPRNG;

/**
 * KernelMutationService – Quản lý "Genotype" của Thiên Đạo (Universe Kernel)
 * 
 * Mỗi vũ trụ có một bộ thông số vận hành (Genome).
 * Khi Fork, genome có thể bị đột biến (Mutation) hoặc kết hợp (Recombination).
 */
class KernelMutationService
{
    private const DEFAULT_GENOME = [
        'diffusion_rate' => 0.1,      // Tốc độ lan truyền các trường (Beta)
        'entropy_coefficient' => 1.0,  // Hệ số sinh entropy (Ec)
        'mutation_rate' => 0.05,       // Tỉ lệ đột biến của genome này (Mu)
        'cohesion_bonus' => 1.0,       // Hệ số đoàn kết (Cb)
        'cognitive_bias' => 1.0,       // Trọng số Field (Gb)
        'attractor_gravity' => 1.0,    // Sức hút của các Attractor Fields
        'complexity_bonus' => 1.0,     // Hệ số thưởng cho tri thức/văn minh
    ];

    /**
     * Khởi tạo genome mặc định cho vũ trụ nếu chưa có.
     */
    public function ensureGenome(Universe $universe): void
    {
        if (!$universe->kernel_genome) {
            $universe->kernel_genome = self::DEFAULT_GENOME;
            $universe->save();
        }
    }

    /**
     * Tạo một genome mới dựa trên genome của parent với đột biến.
     */
    public function mutate(array $parentGenome, Universe $universe): array // Added Universe $universe
    {
        $childGenome = $parentGenome;
        $mutationRate = $parentGenome['mutation_rate'] ?? 0.05;

        $prng = SimulationPRNG::forUniverse($universe); // Used SimulationPRNG
        foreach ($childGenome as $key => $value) { // Changed $baseGenome to $childGenome
            if ($prng->nextInt(0, 1000) / 1000 < $mutationRate) { // Used $prng->nextInt
                // Mutate value slightly
                $factor = 0.8 + ($prng->nextInt(0, 400) / 1000);
                $childGenome[$key] = round($value * $factor, 5);
                
                // Clamp values
                if (in_array($key, ['diffusion_rate', 'mutation_rate'])) {
                    $childGenome[$key] = max(0.001, min(0.9, $childGenome[$key]));
                } else {
                    $childGenome[$key] = max(0.001, min(5.0, $childGenome[$key]));
                }
            }
        }

        return $childGenome;
    }

    /**
     * Tính toán Fitness Score cho vũ trụ để đánh giá "sức sống bản ngã" (Seleciton phase).
     */
    public function calculateFitness(Universe $universe): float
    {
        $metrics = ($universe->state_vector ?? [])['metrics'] ?? [];
        
        $order = 1.0 - min(1.0, ($universe->entropy ?? 0.5));
        $knowledge = $metrics['knowledge_core'] ?? 0.1;
        $stability = $universe->structural_coherence ?? 0.5;
        
        $stateVector = $universe->state_vector ?? [];
        $complexity = (float)($stateVector['phase_score']['information'] ?? 0) * 0.5;
        
        // Fitness = f(Order, Knowledge, Stability, Complexity)
        $score = ($order * 0.2) + ($knowledge * 0.4) + ($stability * 0.2) + ($complexity * 0.2);
        
        return round($score, 4);
    }
}


