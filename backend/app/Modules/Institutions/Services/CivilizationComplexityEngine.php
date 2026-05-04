<?php

namespace App\Modules\Institutions\Services;

use App\Modules\Simulation\Core\Runtime\Domain\UniverseState;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use function resource_path;
use function app;

/**
 * Civilization Complexity Engine via DSL.
 */
class CivilizationComplexityEngine
{
    public function __construct(
        protected ?RuleVmService $ruleVm = null
    ) {
        $this->ruleVm = $ruleVm ?? app(RuleVmService::class);
    }

    public function evaluate(UniverseState $state): UniverseState
    {
        $dslFile = resource_path('worldos_rules/society/dynamics.dsl');
        $dsl = @file_get_contents($dslFile) ?: '';

        $rawState = [
            'energyLevel' => $state->energyLevel,
            'order' => $state->order,
            'civilization_complexity' => $state->civilizationComplexity,
            'entropy' => $state->entropy ?? 0,
            'stability' => $state->stability_index ?? 0,
        ];

        $result = $this->ruleVm->evaluateRawState($rawState, $dsl);
        $dslState = $result['state'] ?? [];

        $state->civilizationComplexity = (float) ($dslState['civilization_complexity'] ?? $state->civilizationComplexity);
        $state->institutionStrength = (float) ($dslState['institution_strength'] ?? 0);
        $state->informationDensity = (float) ($dslState['information_density'] ?? 0);

        $complexityPressure = (float) ($dslState['complexity_pressure'] ?? 0);
        $state->pressures['collapse_pressure'] = max(
            $state->pressures['collapse_pressure'] ?? 0,
            $complexityPressure
        );

        return $state;
    }
}




