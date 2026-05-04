<?php

namespace App\Modules\Simulation\Core\Domain\Pipelines\Steps;

// use App\Modules\Simulation\Core\Domain\Pipelines\SpawnStepInterface;
use Illuminate\Support\Facades\Log;

/**
 * Bước tiêm các quy tắc Meta-Edicts và Inspiration từ World Axiom.
 */
class PreCreateInjectionStep implements SpawnStepInterface
{
    public function execute(array $context): array
    {
        $world = $context['world'];
        $initialState = $context['initial_state'] ?? [];
        $branchPayload = $context['branch_payload'];

        // 1. Branch Mutation từ payload
        if ($initialState && !empty($branchPayload['mutation'])) {
             $mutation = $branchPayload['mutation'];
             if (isset($mutation['suggest_reduce_entropy']) && $mutation['suggest_reduce_entropy']) {
                 $currentEntropy = $initialState['entropy'] ?? 1.0;
                 $initialState['entropy'] = max(0, $currentEntropy * 0.9);
                 Log::info("SpawnPipeline: Entropy reduced by branch mutation.");
             }
        }

        // 2. Inspiration Recycling
        $seed = $world->world_seed ?? [];
        if (!empty($seed['inspiration_pool']) && $seed['inspiration_pool'] > 0) {
            $boost = $seed['inspiration_pool'];
            $initialState['entropy'] = max(0, ($initialState['entropy'] ?? 1.0) - $boost);
            Log::info("SpawnPipeline: Applying Inspiration Boost of {$boost}.");
            
            $seed['inspiration_pool'] = 0;
            $world->update(['world_seed' => $seed]);
        }

        // 3. Meta-Edicts
        $axiom = $world->axiom ?? [];
        $metaEdicts = $axiom['meta_edicts'] ?? [];
        if (!empty($metaEdicts)) {
            $initialState['inherited_meta_edicts'] = array_keys($metaEdicts);
        }

        $context['initial_state'] = $initialState;
        return $context;
    }
}
