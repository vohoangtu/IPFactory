<?php

namespace Tests\Unit\Simulation;

use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use App\Modules\Simulation\Services\Ecology\PressureCalculator;
use PHPUnit\Framework\TestCase;

class PressureCalculatorTest extends TestCase
{
    public function test_calculate_material_stress_clamps_to_zero_for_invalid_negative_inputs(): void
    {
        $calculator = new PressureCalculator();

        $stress = $calculator->calculateMaterialStress([
            'entropy' => -10,
            'base_mass' => 1000,
            'structured_mass' => 0,
        ]);

        $this->assertSame(0.3, $stress);
    }

    public function test_calculate_material_stress_treats_non_positive_base_mass_as_max_depletion(): void
    {
        $calculator = new PressureCalculator();

        $stress = $calculator->calculateMaterialStress([
            'entropy' => 0,
            'base_mass' => 0,
            'structured_mass' => 0,
        ]);

        $this->assertSame(0.3, $stress);
    }

    public function test_calculate_material_stress_does_not_drop_when_structured_mass_exceeds_base_mass(): void
    {
        $calculator = new PressureCalculator();

        $stress = $calculator->calculateMaterialStress([
            'entropy' => 0.6,
            'base_mass' => 100,
            'structured_mass' => 250,
        ]);

        $this->assertGreaterThan(0.0, $stress);
    }

    public function test_calculate_secession_pressure_clamps_and_normalizes_inputs(): void
    {
        $ruleVm = $this->createMock(RuleVmService::class);
        $ruleVm->method('evaluateRawState')->willReturn([
            'state' => ['secession_pressure' => 0.52],
        ]);
        $calculator = new PressureCalculator($ruleVm);

        $pressure = $calculator->calculateSecessionPressure(
            [
                'culture' => ['ritual' => 5.0],
                'institutional_trust' => -10.0,
            ],
            ['culture' => ['ritual' => -3.0]]
        );

        $this->assertSame(0.52, $pressure);
    }

    public function test_calculate_cosmic_metrics_clamps_order_and_energy_level_to_unit_interval_but_preserves_raw_entropy(): void
    {
        $ruleVm = $this->createMock(RuleVmService::class);
        $ruleVm->method('evaluateRawState')->willReturn([
            'state' => ['order' => 0.0, 'energy_level' => 0.97],
        ]);
        $calculator = new PressureCalculator($ruleVm);

        $metrics = $calculator->calculateCosmicMetrics([
            'entropy' => 1.8,
            'base_mass' => 100,
            'structured_mass' => 500,
            'innovation' => 0.9,
        ]);

        $this->assertSame(0.0, $metrics['order']);
        $this->assertSame(0.97, $metrics['energy_level']);
        $this->assertSame(1.8, $metrics['entropy']);
    }

    public function test_calculate_secession_pressure_clamps_lower_bound(): void
    {
        $calculator = new PressureCalculator();

        $pressure = $calculator->calculateSecessionPressure(
            ['institutional_trust' => 10],
            []
        );

        $this->assertSame(0.0, $pressure);
    }
}
