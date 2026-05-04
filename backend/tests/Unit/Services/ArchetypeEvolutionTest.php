<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Modules\Intelligence\Services\Morphogenesis\ArchetypeGenome;
use App\Modules\Intelligence\Services\Morphogenesis\MorphogenesisEngine;
use App\Modules\Intelligence\Services\Morphogenesis\FitnessEvaluator;
use App\Modules\Intelligence\Services\Morphogenesis\EvolutionaryOperator;
use App\Contracts\SimulationEngineClientInterface;
use Mockery;

class ArchetypeEvolutionTest extends TestCase
{
    public function test_evolutionary_cycle()
    {
        // 1. Mock engine
        $engine = Mockery::mock(SimulationEngineClientInterface::class);
        
        // Mock batchAdvance to return various fitness results
        $engine->shouldReceive('batchAdvance')->andReturnUsing(function($requests) {
            $responses = [];
            foreach ($requests as $req) {
                // Randomly generate results for fitness calculation
                $responses[] = [
                    'ok' => true,
                    'snapshot' => [
                        'stability_index' => mt_rand(0, 100) / 100,
                        'entropy' => mt_rand(0, 100) / 100,
                        'state_vector' => ['knowledge' => mt_rand(0, 100) / 100]
                    ]
                ];
            }
            return ['responses' => $responses];
        });

        // 2. Setup Engine
        $evaluator = new FitnessEvaluator($engine);
        $operator = new EvolutionaryOperator();
        $morphEngine = new MorphogenesisEngine($evaluator, $operator);

        // 3. Evolve for 2 generations, population of 5
        $initialState = ['knowledge' => 0.1, 'stability' => 0.9];
        $finalPopulation = $morphEngine->evolve(2, 5, $initialState);

        // 4. Assertions
        $this->assertCount(5, $finalPopulation);
        
        // The top genome should have generation data
        $apex = $finalPopulation[0];
        $this->assertArrayHasKey('generation', $apex->metadata);
        $this->assertArrayHasKey('best_fitness', $apex->metadata);
        $this->assertGreaterThan(0, $apex->metadata['generation']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
