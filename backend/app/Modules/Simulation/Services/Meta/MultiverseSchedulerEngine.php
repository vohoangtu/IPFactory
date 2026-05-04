<?php

namespace App\Modules\Simulation\Services\Meta;

use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use Illuminate\Support\Collection;
use function resource_path;
use function config;
use function collect;
use function file_get_contents;
use function count;
use function round;

/**
 * Multiverse Scheduler Engine: selects which universes get tick budget in a cycle.
 * Priority = weighted sum of novelty, complexity, civilization, entropy (higher = tick first).
 * When tick_budget > 0, only top-N universes per world are scheduled.
 */
class MultiverseSchedulerEngine
{
    public function __construct(
        protected RuleVmService $ruleVm
    ) {}

    /**
     * Return universes to tick for this world, ordered by priority (highest first).
     * If $tickBudget is null, use config. If 0, no limit (return all active).
     */
    public function schedule(World $world, ?int $tickBudget = null): Collection
    {
        $budget = $tickBudget ?? (int) \config('worldos.scheduler.tick_budget', 0);

        $universes = Universe::where('world_id', $world->id)
            ->whereIn('status', ['active', 'running'])
            ->get();

        if ($universes->isEmpty()) {
            return collect();
        }

        $scored = $universes->map(function (Universe $u) {
            return [
                'universe' => $u,
                'priority' => $this->computePriority($u),
            ];
        })->sortByDesc('priority')->values();

        if ($budget > 0) {
            return $scored->take($budget)->pluck('universe');
        }

        return $scored->pluck('universe');
    }

    /**
     * Return universes with priority and order_index for dashboard/simulation-status API.
     * Same as schedule() but returns full scored items: [universe, priority, order_index].
     */
    public function scheduleWithScores(World $world, ?int $tickBudget = null): Collection
    {
        $budget = $tickBudget ?? (int) \config('worldos.scheduler.tick_budget', 0);

        $universes = Universe::where('world_id', $world->id)
            ->whereIn('status', ['active', 'running'])
            ->get();

        if ($universes->isEmpty()) {
            return collect();
        }

        $scored = $universes->map(function (Universe $u) {
            return [
                'universe' => $u,
                'priority' => $this->computePriority($u),
            ];
        })->sortByDesc('priority')->values();

        if ($budget > 0) {
            $scored = $scored->take($budget);
        }

        return $scored->map(function ($item, $index) {
            $item['order_index'] = $index + 1;
            return $item;
        })->values();
    }

    /**
     * Priority score from state_vector and universe (novelty proxy, complexity, civilization, entropy).
     */
    protected function computePriority(Universe $universe): float
    {
        $vec = (array) ($universe->state_vector ?? []);
        $zones = (array) ($vec['zones'] ?? []);
        $civ = (array) ($vec['civilization'] ?? []);
        $settlements = (array) ($civ['settlements'] ?? []);
        $fields = (array) ($vec['fields'] ?? []);

        $vmState = [
            'entropy' => (float) ($universe->entropy ?? $vec['entropy'] ?? 0.5),
            'count_zones' => count($zones),
            'count_settlements' => count($settlements),
            'f_survival' => (float) ($fields['survival'] ?? 0.5),
            'f_power' => (float) ($fields['power'] ?? 0.0),
            'f_wealth' => (float) ($fields['wealth'] ?? 0.0),
            'f_knowledge' => (float) ($fields['knowledge'] ?? 0.0),
            'f_meaning' => (float) ($fields['meaning'] ?? 0.0),
        ];

        $dslFile = \resource_path('worldos_rules/multiverse/scheduling.dsl');
        $dsl = @file_get_contents($dslFile) ?: '';
        
        $result = $this->ruleVm->evaluateRawState($vmState, $dsl);
        return round((float) ($result['state']['priority_score'] ?? 0.5), 4);
    }
}




