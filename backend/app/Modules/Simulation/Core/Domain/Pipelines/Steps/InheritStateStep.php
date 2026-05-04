<?php

namespace App\Modules\Simulation\Core\Domain\Pipelines\Steps;

use App\Modules\World\Models\Universe;
// use App\Modules\Simulation\Core\Domain\Pipelines\SpawnStepInterface;

/**
 * Bước kế thừa state từ parent universe.
 */
class InheritStateStep implements SpawnStepInterface
{
    public function execute(array $context): array
    {
        $parentUniverseId = $context['parent_universe_id'];

        if ($parentUniverseId) {
            $parent = Universe::find($parentUniverseId);
            if ($parent) {
                $latest = $parent->snapshots()->orderByDesc('tick')->first();
                $context['initial_state'] = $latest?->state_vector ?? $parent->state_vector;
                $context['start_tick'] = $latest?->tick ?? $parent->current_tick;
                $context['parent_genome'] = $parent->kernel_genome ?? [];
            }
        }

        return $context;
    }
}

