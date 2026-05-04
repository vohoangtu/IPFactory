<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Modules\Intelligence\Services\MetaLearning\ParameterGenome;
use App\Modules\Intelligence\Services\MetaLearning\MetaLearningEngine;
use App\Modules\Intelligence\Services\MetaLearning\HypothesisGenerator;
use App\Contracts\SimulationEngineClientInterface;
use Mockery;

class IntelligenceExplosionTest extends TestCase
{
    public function test_meta_learning_and_theory_discovery()
    {
        // 1. Mock engine
        $engine = Mockery::mock(SimulationEngineClientInterface::class);
        
        // Mock advance to return increasing SCI (Complexity Index)
        $counter = 0;
        $engine->shouldReceive('advance')->andReturnUsing(function() use (&$counter) {
            $counter++;
            return [
                'ok' => true,
                'snapshot' => [
                    'sci' => 0.5 + ($counter * 1.5),
                    'entropy' => 0.5,
                ]
            ];
        });

        // 2. Setup Engines
        $metaEngine = new MetaLearningEngine($engine);
        $theoryEngine = new HypothesisGenerator();

        // 3. Run Optimization (3 iterations)
        $baseConfig = ['delta' => 0.5, 'contraction' => 0.5];
        $baseGenome = new ParameterGenome($baseConfig);
        
        $optimized = $metaEngine->optimize(3, $baseGenome);
        
        // 4. Generate Hypotheses
        $hypotheses = $theoryEngine->generate($optimized, $baseGenome);

        // 5. Assertions
        $this->assertNotEquals($baseConfig, $optimized->worldConfig);
        $this->assertNotEmpty($hypotheses);
        
        // Check if statement is generated
        $this->assertStringContainsString('leads to higher emergent complexity', $hypotheses[0]['statement']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
