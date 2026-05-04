<?php

namespace App\Modules\Institutions\Services;

use App\Modules\Simulation\Core\Runtime\Domain\UniverseState;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use App\Modules\Simulation\Core\Support\SimulationRandom;
use function resource_path;

/**
 * Entropy Engine: Thermodynamics of Civilization via DSL.
 */
class EntropyEngine
{
    public function __construct(
        protected RuleVmService $ruleVm
    ) {}

    public function evaluate(UniverseState $state): UniverseState
    {
        $rng = new SimulationRandom($state->seed, $state->tick, 3);
        $dslFile = resource_path('worldos_rules/physics/core.dsl');
        $dsl = @file_get_contents($dslFile) ?: '';

        // Prepare raw state for DSL
        $rawState = [
            'entropy' => (float) $state->entropy,
            'order' => (float) $state->order,
            'energyLevel' => (float) $state->energyLevel,
            'civilizationCount' => (int) $state->civilizationCount,
            'civilizationComplexity' => (float) $state->civilizationComplexity,
            'chaos' => (float) ($state->pressures['chaos'] ?? 0),
            'random_chaos_noise' => $rng->float(-0.002, 0.002),
            'collapse_pressure' => (float) ($state->pressures['collapse_pressure'] ?? 0),
            'ascension_pressure' => (float) ($state->pressures['ascension_pressure'] ?? 0),
            'axioms' => $state->axioms,
        ];

        $result = $this->ruleVm->evaluateRawState($rawState, $dsl);
        $finalState = $result['state'] ?? [];

        // Apply back to formal state object
        $state->entropy = (float) ($finalState['entropy'] ?? $state->entropy);
        $state->order = (float) ($finalState['order'] ?? $state->order);
        
        $state->pressures['collapse_pressure'] = (float) ($finalState['collapse_pressure'] ?? 0);
        $state->pressures['ascension_pressure'] = (float) ($finalState['ascension_pressure'] ?? 0);
        $state->pressures['chaos'] = (float) ($finalState['chaos'] ?? 0);
        
        $state->axioms = $finalState['axioms'] ?? $state->axioms;

        return $state;
    }
}




