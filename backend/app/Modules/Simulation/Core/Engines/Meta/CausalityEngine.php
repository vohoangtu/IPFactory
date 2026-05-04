<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use App\Modules\Simulation\Core\Effects\WorldRulesUpdateEffect;
use function resource_path;
use function file_get_contents;
use function app;

/**
 * doc §12.1: Causality Engine — causal graph (Event A → Event B → Event C).
 * Pipeline representation; actual causality graph update is done by SyncWorldEventToCausalityGraph
 * when events are published (doc §4 event flow).
 */
final class CausalityEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    public function __construct(
        private ?RuleVmService $ruleVm = null
    ) {
        $this->ruleVm = $this->ruleVm ?? \app(RuleVmService::class);
    }

    public function phase(): string
    {
        return 'meta';
    }

    public function name(): string
    {
        return 'causality';
    }

    public function priority(): int
    {
        return 24;
    }

    public function tickRate(): int
    {
        return 5;
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $dslFile = \resource_path('worldos_rules/simulation/integrity.dsl');
        $dsl = @file_get_contents($dslFile) ?: '';

        $vec = $state->getStateVector();
        
        $rawState = [
            'causal_debt' => (float) ($vec['meta']['causal_debt'] ?? 0.0),
            'causal_integrity' => (float) ($vec['meta']['causal_integrity'] ?? 1.0),
            'entropy' => (float) ($state->getEntropy() ?? 0.5),
        ];

        $result = $this->ruleVm->evaluateRawState($rawState, $dsl);
        
        $effects = [];
        if ($result['ok'] ?? false) {
            $fs = $result['state'] ?? [];
            if (isset($fs['causal_integrity'])) {
                $effects[] = new WorldRulesUpdateEffect([
                    'meta.causal_integrity' => (float)$fs['causal_integrity']
                ]);
            }
        }

        return new EngineResult([], $effects, []);
    }
}



