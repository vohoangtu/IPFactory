<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Effects\WorldRulesUpdateEffect;
use App\Modules\Simulation\Core\Events\WorldEvent;
use App\Modules\Simulation\Core\Events\WorldEventType;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use Illuminate\Support\Facades\Log;
use function app;

/**
 * Evolves world_rules (Tier 2 mutable rules) via DSL logic.
 */
class LawEvolutionEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    public function __construct(
        protected ?RuleVmService $ruleVm = null
    ) {
        $this->ruleVm = $ruleVm ?? app(RuleVmService::class);
    }

    public function phase(): string
    {
        return 'meta';
    }

    private const MUTABLE_KEYS = ['entropy_tendency', 'order_tendency', 'innovation_tendency'];

    public function name(): string
    {
        return 'law_evolution';
    }

    public function priority(): int
    {
        return 6;
    }

    public function tickRate(): int
    {
        return max(1, (int) (\config('worldos.time_scale_factors.law_evolution') ?? 20));
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $this->ruleVm->evaluateAndApplyWithDsl($state, 'innovation/leadership', $ctx->getTick());

        Log::info("LawEvolutionEngine: World rules evolved via DSL for Universe {$state->get('universe_id')} at tick {$ctx->getTick()}");

        return new EngineResult([], [], []);
    }
}


