<?php

namespace App\Modules\Simulation\Core\Domain\Pipelines\Steps;

use App\Modules\World\Models\Universe;

// use App\Modules\Simulation\Core\Domain\Pipelines\SpawnStepInterface;
use App\Modules\Simulation\Services\Ecology\KernelMutationService;

/**
 * Bước đột biến genome cho vũ trụ mới.
 */
class MutateGenomeStep implements SpawnStepInterface
{
    public function __construct(
        protected KernelMutationService $mutationService
    ) {}

    public function execute(array $context): array
    {
        $parentGenome = $context['parent_genome'] ?? [];
        $parentUniverseId = $context['parent_universe_id'];

        if ($parentUniverseId) {
            $parent = \App\Modules\World\Models\Universe::find($parentUniverseId);
            $context['genome'] = $this->mutationService->mutate($parentGenome, $parent);
        }

        return $context;
    }
}


