<?php

namespace App\Modules\Simulation\Services\Ecology;

use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use function array_merge;
use function abs;
use function max;
use function min;

class PressureCalculator
{
    public function __construct(
        protected ?RuleVmService $ruleVm = null
    ) {}

    /**
     * Calculate Material Stress for a given state (zone or universe).
     */
    public function calculateMaterialStress(array $state): float
    {
        if ($this->ruleVm !== null) {
            $result = $this->evaluateWithDsl($state);
            return (float) ($result['state']['material_stress'] ?? 0.0);
        }

        // Fallback direct calculation
        $entropy = max(0.0, (float) ($state['entropy'] ?? 0.0));
        $baseMass = (float) ($state['base_mass'] ?? 0);
        $structuredMass = (float) ($state['structured_mass'] ?? 0);

        if ($baseMass <= 0) {
            return 0.3;
        }

        $depletionRatio = 1.0 - min(1.0, $structuredMass / $baseMass);
        $stress = $entropy * 0.4 + $depletionRatio * 0.3 + ($entropy * 1.5) * 0.3;

        return max(0.0, min(1.0, $stress));
    }

    /**
     * Calculate Secession Pressure (Pz) for a zone.
     */
    public function calculateSecessionPressure(array $zoneState, array $globalState): float
    {
        if ($this->ruleVm !== null) {
            $evalState = array_merge($zoneState, [
                'global_culture' => $globalState['culture'] ?? []
            ]);
            $result = $this->evaluateWithDsl($evalState);
            return (float) ($result['state']['secession_pressure'] ?? 0.0);
        }

        // Fallback direct calculation
        $trust = max(0.0, min(1.0, (float) ($zoneState['institutional_trust'] ?? 0.5)));
        $zCulture = $zoneState['culture'] ?? [];
        $gCulture = $globalState['culture'] ?? [];

        $distance = $this->calculateCultureDistance($zCulture, $gCulture);
        $pressure = $distance * 0.6 + (1.0 - $trust) * 0.4 - $trust * 0.2;

        return max(0.0, min(1.0, $pressure));
    }

    /**
     * Calculate global cosmic metrics: Order and Energy Level.
     */
    public function calculateCosmicMetrics(array $state): array
    {
        if ($this->ruleVm !== null) {
            $result = $this->evaluateWithDsl($state);
            $finalState = $result['state'] ?? [];
            return [
                'order' => (float) ($finalState['order'] ?? 1.0),
                'energy_level' => (float) ($finalState['energy_level'] ?? 0.5),
                'entropy' => (float) ($state['entropy'] ?? 0.0),
            ];
        }

        // Fallback direct calculation
        $entropy = (float) ($state['entropy'] ?? 0.0);
        $baseMass = (float) ($state['base_mass'] ?? 0);
        $structuredMass = (float) ($state['structured_mass'] ?? 0);
        $innovation = (float) ($state['innovation'] ?? 0.0);

        $order = $baseMass > 0
            ? max(0.0, 1.0 - ($entropy * 0.5 + (1.0 - min(1.0, $structuredMass / $baseMass)) * 0.5))
            : 1.0 - $entropy;

        $energyLevel = min(1.0, $innovation + ($structuredMass / max(1.0, $baseMass)) * 0.5);

        return [
            'order' => round(max(0.0, min(1.0, $order)), 2),
            'energy_level' => round(max(0.0, min(1.0, $energyLevel)), 2),
            'entropy' => $entropy,
        ];
    }

    /**
     * Manhattan distance for cultural vectors.
     */
    protected function calculateCultureDistance(array $zCulture, array $gCulture): float
    {
        if (empty($zCulture) || empty($gCulture)) {
            return 0.0;
        }

        $sum = 0;
        $count = 0;
        foreach ($zCulture as $key => $val) {
            if (isset($gCulture[$key])) {
                $zoneValue = $this->normalizeRatio((float) $val);
                $globalValue = $this->normalizeRatio((float) $gCulture[$key]);
                $sum += abs($zoneValue - $globalValue);
                $count++;
            }
        }

        return $count > 0 ? ($sum / $count) : 0.0;
    }

    protected function evaluateWithDsl(array $state): array
    {
        if (function_exists('app') && app()->bound('path.resources')) {
            $dslFile = resource_path('worldos_rules/simulation/pressures.dsl');
            $dsl = @file_get_contents($dslFile) ?: '';
        } else {
            $dsl = '';
        }
        return $this->ruleVm->evaluateRawState($state, $dsl);
    }

    protected function normalizeRatio(float $value): float
    {
        return max(0.0, min(1.0, $value));
    }
}
