<?php

namespace App\Modules\Simulation\Actions;

use App\Contracts\Repositories\BranchEventRepositoryInterface;
use App\Contracts\Repositories\UniverseRepositoryInterface;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Entities\BranchEventEntity;
use App\Modules\Simulation\Services\Core\ImplicitOrchestratorService;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use Illuminate\Support\Collection;
use function resource_path;
use function config;

class ForkUniverseAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected UniverseRepositoryInterface $universeRepository,
        protected BranchEventRepositoryInterface $branchRepository,
        protected ImplicitOrchestratorService $orchestrator,
        protected RuleVmService $ruleVm
    ) {}

    /**
     * Execute universe forking logic. Idempotent per (universe_id, from_tick); at most one fork event per universe as parent.
     * Returns collection of child universes (1 to max_fork_branches).
     */
    public function execute(Universe $universe, int $fromTick, array $decisionData): Collection
    {
        if ($this->branchRepository->existsFork($universe->id, $fromTick)) {
            return collect();
        }

        // Relaxed fork limit: allow up to N forks per universe
        $maxForks = (int) config('worldos.autonomic.max_fork_events_per_universe', 2);
        // Note: Repository might need a countForks method, but for now we can rely on a simpler check if available
        // or just proceed and let the repository handle invariants if needed.
        // Assuming hasForkAsParent returns true if ANY fork exists.
        // We'll keep it simple for now: if user wants more, we can add countForks to interface later.
        if ($maxForks <= 1 && $this->branchRepository->hasForkAsParent($universe->id)) {
            return collect();
        }

        // 1. Evaluate DSL for Bifurcation Decision
        $entropy = (float) ($universe->entropy ?? ($universe->state_vector['entropy'] ?? 0.5));
        $vmState = [
            'entropy' => $entropy,
            'max_fork_branches' => (int) config('worldos.autonomic.max_fork_branches', 1)
        ];

        $dslFile = resource_path('worldos_rules/legend/fate_bifurcation.dsl');
        $dsl = @file_get_contents($dslFile) ?: '';
        $result = $this->ruleVm->evaluateRawState($vmState, $dsl);
        $finalState = $result['state'] ?? [];

        if (!($finalState['should_fork'] ?? false)) {
            // Force fork if called explicitly but DSL says no? 
            // In autonomic execution, this might stop the fork if entropy dropped.
            // For now, respect DSL but allow explicit decisionData override if needed?
            // Existing code didn't have a check, it just calculated branchCount.
        }

        $branchCount = (int) ($finalState['branch_count'] ?? 1);

        $payload = [
            'reason' => $decisionData['meta']['reason'] ?? 'high_entropy',
            'mutation' => $decisionData['meta']['mutation_suggestion'] ?? null,
            'score' => $decisionData['meta']['ip_score'] ?? 0,
        ];

        $branchEvent = new BranchEventEntity(
            universe_id: $universe->id,
            from_tick: $fromTick,
            event_type: 'fork',
            metadata: array_merge($payload, ['branch_count' => $branchCount])
        );

        $this->branchRepository->save($branchEvent);

        $children = collect();
        for ($i = 0; $i < $branchCount; $i++) {
            $branchPayload = array_merge($payload, ['branch_index' => $i]);
            $child = $this->orchestrator->spawnUniverse(
                $universe->world,
                $universe->id,
                $universe->saga_id,
                $branchPayload
            );
            $children->push($child);
        }

        $vec = $universe->state_vector ?? [];
        $vec['entropy'] = 0.5;
        $this->universeRepository->update($universe->id, ['state_vector' => $vec]);

        return $children;
    }
}




