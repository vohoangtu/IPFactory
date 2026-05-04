<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Modules\Intelligence\Services\Analysis\TrajectoryRecorder;
use App\Modules\Intelligence\Services\Analysis\RegimeSequenceAnalyzer;
use App\Modules\Intelligence\Services\Analysis\StrangeAttractorDetector;
use App\Modules\Intelligence\Services\Analysis\DarkAttractorDetector;
use App\Contracts\SimulationEngineClientInterface;
use Mockery;

class CivilizationAnalysisTest extends TestCase
{
    public function test_full_analysis_pipeline()
    {
        // 1. Setup mock engine
        $engine = Mockery::mock(SimulationEngineClientInterface::class);
        
        // Mock analyzeTrajectory for strange attractor test
        $engine->shouldReceive('analyzeTrajectory')->andReturn([
            'ok' => true,
            'is_bounded' => true,
            'is_recurrent' => true,
            'max_lyapunov_estimate' => 0.05,
            'recurrence_rate' => 0.25,
            'trajectory_variance' => 0.5,
            'basin_center' => [0.5, 0.5, 0.5],
            'basin_radius' => 0.8,
            'regime_transitions' => [],
        ]);

        // Mock batchAdvance for dark attractor test
        $engine->shouldReceive('batchAdvance')->andReturn([
            'responses' => [
                ['ok' => true, 'snapshot' => ['state_vector' => ['knowledge' => 0.1, 'stability' => 0.9, 'coercion' => 0.1]]],
                ['ok' => true, 'snapshot' => ['state_vector' => ['knowledge' => 0.11, 'stability' => 0.89, 'coercion' => 0.09]]],
                ['ok' => true, 'snapshot' => ['state_vector' => ['knowledge' => 0.09, 'stability' => 0.91, 'coercion' => 0.1]]],
            ]
        ]);

        // 2. Trajectory Recording
        $recorder = new TrajectoryRecorder();
        for ($i = 0; $i < 4; $i++) {
            $recorder->record(1 + $i*3, ['knowledge' => 0.1, 'stability' => 0.9], 'Warlord');
            $recorder->record(2 + $i*3, ['knowledge' => 0.2, 'stability' => 0.8], 'Warlord');
            $recorder->record(3 + $i*3, ['knowledge' => 0.3, 'stability' => 0.7], 'Technocrat');
        }

        // 3. Regime Sequence Analysis
        $regimeAnalyzer = new RegimeSequenceAnalyzer();
        $sequence = $recorder->getRegimeSequence();
        $analysis = $regimeAnalyzer->analyze($sequence);
        
        $this->assertEquals(['Warlord', 'Warlord', 'Technocrat'], $analysis['pattern']);
        $this->assertTrue($analysis['is_periodic']);

        // 4. Strange Attractor Detection (Rust-backed)
        $strangeDetector = new StrangeAttractorDetector($engine);
        $trajectoryResult = $strangeDetector->detect($recorder->getFlattenedTrajectory());
        
        $this->assertTrue($trajectoryResult['is_strange_attractor']);
        $this->assertTrue($trajectoryResult['is_bounded']);
        $this->assertGreaterThan(0, $trajectoryResult['max_lyapunov_estimate']);

        // 5. Dark Attractor Detection (Perturbation-backed)
        $darkDetector = new DarkAttractorDetector($engine);
        $trapResult = $darkDetector->detect(1, ['knowledge' => 0.1, 'stability' => 0.9, 'coercion' => 0.1]);
        
        $this->assertTrue($trapResult['is_dark_attractor']);
        $this->assertEquals('Stagnation Trap', $trapResult['trap_type']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
