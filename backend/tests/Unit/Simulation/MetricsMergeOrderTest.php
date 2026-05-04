<?php

namespace Tests\Unit\Simulation;

use PHPUnit\Framework\TestCase;

/**
 * Verifies the metrics merge contract: array_replace_recursive(calculated_metrics, snapshot->metrics)
 * must preserve cosmic impact from SupremeEntity (ethos, energy_level, entropy) so that
 * EvaluateSimulationResult::storePressureMetrics keeps them when persisting.
 */
class MetricsMergeOrderTest extends TestCase
{
    public function test_merge_preserves_snapshot_metrics_over_calculated(): void
    {
        $calculated_metrics = [
            'material_stress' => 0.3,
            'order' => 0.7,
            'energy_level' => 0.4,
        ];
        $snapshot_metrics = [
            'ethos' => [
                'spirituality' => 0.9,
                'openness' => 0.6,
                'rationality' => 0.6,
                'hardtech' => 0.2,
            ],
            'energy_level' => 0.82,
        ];

        $merged = array_replace_recursive($calculated_metrics, $snapshot_metrics);

        $this->assertSame(0.9, $merged['ethos']['spirituality']);
        $this->assertSame(0.82, $merged['energy_level']);
        $this->assertSame(0.3, $merged['material_stress']);
        $this->assertSame(0.7, $merged['order']);
    }

    public function test_merge_keeps_entropy_on_snapshot_object_when_set(): void
    {
        $calculated = ['material_stress' => 0.2, 'order' => 0.8, 'energy_level' => 0.5];
        $snapshot_metrics = ['energy_level' => 0.75];
        $merged = array_replace_recursive($calculated, $snapshot_metrics);
        $this->assertSame(0.75, $merged['energy_level']);
    }
}
