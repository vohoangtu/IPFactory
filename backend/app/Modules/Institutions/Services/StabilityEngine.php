<?php

namespace App\Modules\Institutions\Services;

use App\Modules\Simulation\Core\Runtime\Domain\UniverseState;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use function resource_path;

/**
 * Stability Engine: Feedback controller for the universe via DSL.
 */
class StabilityEngine
{
    public function __construct(
        protected RuleVmService $ruleVm
    ) {}

    public function evaluate(UniverseState $state): UniverseState
    {
        $dslFile = resource_path('worldos_rules/physics/core.dsl');
        $dsl = @file_get_contents($dslFile) ?: '';

        $rawState = [
            'entropy' => (float) $state->entropy,
            'order' => (float) $state->order,
            'energyLevel' => (float) $state->energyLevel,
            'civilizationCount' => (int) $state->civilizationCount,
            'chaos' => (float) ($state->pressures['chaos'] ?? 0),
            'collapse_pressure' => (float) ($state->pressures['collapse_pressure'] ?? 0),
        ];

        $result = $this->ruleVm->evaluateRawState($rawState, $dsl);
        $finalState = $result['state'] ?? [];

        // Apply stabilizers back
        $state->entropy = (float) ($finalState['entropy'] ?? $state->entropy);
        $state->order = (float) ($finalState['order'] ?? $state->order);
        $state->pressures['collapse_pressure'] = (float) ($finalState['collapse_pressure'] ?? 0);
        $state->pressures['chaos'] = (float) ($finalState['chaos'] ?? 0);

        return $state;
    }
}




