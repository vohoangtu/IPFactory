<?php

namespace App\Modules\Simulation\Core\Engines\Biological;

use App\Contracts\Repositories\UniverseRepositoryInterface;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Services\EcosystemMetricsService;
use App\Modules\Simulation\Core\SimulationEventBus;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use Illuminate\Support\Facades\Log;
use function resource_path;
use function config;
use function lcg_value;
use function file_get_contents;
use function is_array;
use function json_decode;
use function is_string;

/**
 * Ecological Collapse Engine via DSL.
 */
class EcologicalCollapseEngine
{
    public function __construct(
        protected RuleVmService $ruleVm
    ) {}

    public function runWithState(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, int $currentTick): void
    {
        $interval = (int) config('worldos.intelligence.ecological_collapse_tick_interval', 50);
        if ($interval <= 0 || $currentTick % $interval !== 0) {
            return;
        }

        // Phase 73: Pure State Ecology Alignment
        $this->ruleVm->evaluateAndApplyWithDsl(
            $state, 
            'biology/biosphere', 
            $currentTick,
            ['mode' => 'ECOLOGICAL_COLLAPSE_CHECK']
        );

        // Events like ECOLOGICAL_COLLAPSE_TRIGGERED are handled centrally by RuleVmService via SimulationEventOccurred.
        // If we still need custom Chronicle logic, we can also use a custom listener or keep it here if preferred.
        // For V10, we prefer central event handling.
    }

    public function evaluate(Universe $universe, int $currentTick): void
    {
        // Deprecated
    }

    // handleTriggerCollapseWithState and endCollapseWithState are now handled by DSL effects and central listeners

    private function getStateVector(Universe $universe): array
    {
        $sv = $universe->state_vector;
        if (is_string($sv)) {
            $sv = json_decode($sv, true) ?? [];
        }
        return is_array($sv) ? $sv : [];
    }

    public function handle(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, \App\Modules\Simulation\Core\Domain\TickContext $ctx): \App\Modules\Simulation\Core\Engines\EngineResult
    {
        $this->runWithState($state, $ctx->getTick());
        return \App\Modules\Simulation\Core\Engines\EngineResult::empty();
    }
}





