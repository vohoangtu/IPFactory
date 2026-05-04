<?php

namespace App\Modules\Intelligence\Actions;

use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

/**
 * Phase 38: Manual Genome Override.
 * Allows setting specific genes in the target_genome for smooth transition.
 */
class SetUniverseGenomeAction
implements \App\Contracts\ActionInterface {
    /**
     * Set specific genes or the entire target genome.
     */
    public function execute(Universe $universe, array $genes): void
    {
        $stateVector = $universe->state_vector ?? [];
        $targetGenome = $stateVector['target_genome'] ?? $universe->kernel_genome ?? [];

        foreach ($genes as $gene => $value) {
            // Validation: Ensure values are within reasonable bounds
            $targetGenome[$gene] = round(max(0.0, min(10.0, (float)$value)), 5);
        }

        $stateVector['target_genome'] = $targetGenome;
        $universe->state_vector = $stateVector;
        $universe->save();

        Log::info("GENOME: Manual override applied to target_genome for Universe #{$universe->id}.");
    }
}

