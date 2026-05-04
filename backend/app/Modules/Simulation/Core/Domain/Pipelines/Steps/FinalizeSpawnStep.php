<?php

namespace App\Modules\Simulation\Core\Domain\Pipelines\Steps;

// use App\Modules\Simulation\Core\Domain\Pipelines\SpawnStepInterface;
use App\Modules\Simulation\Services\Cosmology\OriginSeeder;
use App\Modules\Simulation\Services\Ecology\KernelMutationService;

/**
 * Bước hoàn tất: Seeding, Mutation post-processing, External Shocks.
 */
class FinalizeSpawnStep implements SpawnStepInterface
{
    public function __construct(
        protected OriginSeeder $originSeeder,
        protected KernelMutationService $mutationService
    ) {}

    public function execute(array $context): array
    {
        $universe = $context['universe'];
        $parentUniverseId = $context['parent_universe_id'];
        $branchPayload = $context['branch_payload'];

        // Đảm bảo genome (mặc định nếu null)
        $this->mutationService->ensureGenome($universe);

        // Phase 21: Inject External Shock nếu là fork
        if ($parentUniverseId && $branchPayload) {
            app(\App\Modules\Narrative\Actions\InjectExternalShockAction::class)->execute($universe, $branchPayload);
        }

        // Genesis: Seed Origin nếu là root universe
        if (!$parentUniverseId) {
            $this->originSeeder->seed($universe);
        }

        return $context;
    }
}


