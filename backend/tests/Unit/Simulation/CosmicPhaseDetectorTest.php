<?php

namespace Tests\Unit\Simulation;

use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Services\Cosmology\CosmicPhaseDetector;
use Tests\TestCase;

class CosmicPhaseDetectorTest extends TestCase
{
    public function test_detect_returns_dominant_phase_and_strength(): void
    {
        $detector = new CosmicPhaseDetector();
        $snapshot = new UniverseSnapshot(['entropy' => 0.2]);
        $metrics = [
            'ethos' => ['spirituality' => 0.8, 'openness' => 0.3],
        ];

        $result = $detector->detect($snapshot, $metrics);

        $this->assertArrayHasKey('current_phase', $result);
        $this->assertContains($result['current_phase'], ['faith', 'chaos', 'order', 'tech']);
        $this->assertArrayHasKey('phase_strength', $result);
        $this->assertArrayHasKey('scores', $result);
        $this->assertSame(['faith', 'chaos', 'order', 'tech'], array_keys($result['scores']));
    }

    public function test_detect_faith_dominant_when_spirituality_high(): void
    {
        $detector = new CosmicPhaseDetector();
        $snapshot = new UniverseSnapshot(['entropy' => 0.3]);
        $metrics = [
            'ethos' => ['spirituality' => 0.9, 'openness' => 0.2],
        ];

        $result = $detector->detect($snapshot, $metrics);

        $this->assertSame('faith', $result['current_phase']);
        $this->assertGreaterThanOrEqual(0.9, $result['scores']['faith']);
    }

    public function test_detect_chaos_dominant_when_entropy_high(): void
    {
        $detector = new CosmicPhaseDetector();
        $snapshot = new UniverseSnapshot(['entropy' => 0.95]);
        $metrics = [
            'ethos' => ['spirituality' => 0.3, 'openness' => 0.3],
        ];

        $result = $detector->detect($snapshot, $metrics);

        $this->assertSame('chaos', $result['current_phase']);
    }
}
