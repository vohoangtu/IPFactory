<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Modules\Intelligence\Services\Consciousness\SelfModel;
use App\Modules\Intelligence\Services\Consciousness\FutureSimulator;
use App\Modules\Intelligence\Services\Consciousness\StrategyEngine;
use App\Modules\Simulation\Models\CivilizationAttractor;
use App\Contracts\SimulationEngineClientInterface;
use Mockery;

class ConsciousnessPredictionTest extends TestCase
{
    public function test_consciousness_risk_analysis_and_strategy()
    {
        // 1. Mock engine
        $engine = Mockery::mock(SimulationEngineClientInterface::class);
        
        // Mock batchAdvance to return a "Collapse" scenario
        $engine->shouldReceive('batchAdvance')->andReturn([
            'responses' => [
                [
                    'ok' => true,
                    'snapshot' => [
                        'stability_index' => 0.1, // High risk of collapse
                        'entropy' => 0.5,
                        'state_vector' => ['knowledge' => 0.2]
                    ]
                ]
            ]
        ]);

        // 2. Setup Engines
        $simulator = new FutureSimulator($engine);
        $strategy = new StrategyEngine();

        // 3. Current State / SelfModel
        $attractor1 = new CivilizationAttractor(['name' => 'Order Rule', 'force_map' => ['Warlord' => 1.5]]);
        $model = new SelfModel('civ_001', ['stability' => 0.3, 'knowledge' => 0.1], [$attractor1]);

        // 4. Predict & Analyze
        $futures = $simulator->predict($model, 1);
        $risks = $simulator->analyzeRisks($futures);
        
        $this->assertContains('Collapse Risk Detected', $risks);

        // 5. Derive Strategy
        $recommendations = $strategy->recommend($risks, [$attractor1]);
        
        // 6. Assertions
        $this->assertNotEmpty($recommendations);
        $this->assertEquals('Activate Attractor', $recommendations[0]['action']);
        $this->assertEquals('Order Rule', $recommendations[0]['target']);
        $this->assertStringContainsString('stability', $recommendations[0]['reason']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
