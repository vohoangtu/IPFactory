<?php

namespace App\Modules\Intelligence\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Services\Ecology\SimulationPRNG;

/**
 * Phase 31: Universe Mutation Action.
 * Mutates the kernel_genome of a universe when fitness conditions are met.
 */
class UniverseMutationAction
implements \App\Contracts\ActionInterface {
    /**
     * Mutate the genome of the universe.
     */
    public function mutate(Universe $universe, SimulationPRNG $rng): void
    {
        $genome = $universe->kernel_genome ?? [
            'diffusion_rate' => 0.1,
            'mutation_rate' => 0.05,
            'cohesion_bonus' => 1.0
        ];

        $mutationRate = (float) ($genome['mutation_rate'] ?? 0.05);

        // Mutate each gene slightly
        $genome['diffusion_rate'] = $this->mutateGene($genome['diffusion_rate'] ?? 0.1, $mutationRate, $rng);
        $genome['mutation_rate'] = $this->mutateGene($genome['mutation_rate'] ?? 0.05, $mutationRate * 0.5, $rng);
        $genome['cohesion_bonus'] = $this->mutateGene($genome['cohesion_bonus'] ?? 1.0, $mutationRate, $rng);

        $universe->kernel_genome = $genome;
        $universe->save();
    }

    private function mutateGene(float $value, float $rate, SimulationPRNG $rng): float
    {
        // Drift by +/- mutation rate
        $drift = ($rng->nextFloat() * 2 - 1) * $rate;
        return round(max(0.01, min(2.0, $value + $drift)), 4);
    }
}
