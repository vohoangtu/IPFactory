<?php

namespace App\Modules\Intelligence\Services\MetaLearning;

use App\Contracts\SimulationEngineClientInterface;

/**
 * Meta-Learning Engine (Layer 8).
 * Evolves the simulation's "Physics" (parameters) to discover more complex civilization dynamics.
 */
class MetaLearningEngine
{
    public function __construct(
        private SimulationEngineClientInterface $engine
    ) {}

    /**
     * Evolve simulation parameters.
     * 
     * @param int $iterations
     * @param ParameterGenome $baseGenome
     * @return ParameterGenome The optimized genome.
     */
    public function optimize(int $iterations, ParameterGenome $baseGenome): ParameterGenome
    {
        $current = $baseGenome;
        $bestFitness = -1.0;
        $bestGenome = $baseGenome;

        for ($i = 0; $i < $iterations; $i++) {
            // 1. Mutate parameters
            $candidate = $this->mutate($current);

            // 2. Evaluate fitness (meta-fitness: how much 'interesting' stuff happens)
            $fitness = $this->evaluateMetaFitness($candidate);

            if ($fitness > $bestFitness) {
                $bestFitness = $fitness;
                $bestGenome = $candidate;
                $current = $candidate;
            }
        }

        return $bestGenome;
    }

    private function mutate(ParameterGenome $genome): ParameterGenome
    {
        $config = $genome->worldConfig;
        
        // Mutate numeric parameters (delta, contraction, etc.)
        foreach ($config as $key => $val) {
            if (is_numeric($val) && mt_rand(0, 10) > 7) {
                $config[$key] = max(0, min(1, $val + (mt_rand(-10, 10) / 100)));
            }
        }

        return new ParameterGenome($config, array_merge($genome->metadata, ['iteration' => ($genome->metadata['iteration'] ?? 0) + 1]));
    }

    /**
     * Meta-fitness: runs a simulation and measures "Emergence Score".
     */
    private function evaluateMetaFitness(ParameterGenome $genome): float
    {
        // We run a simulation with these parameters
        $res = $this->engine->advance(0, 20, [], $genome->worldConfig);
        
        if (!($res['ok'] ?? false)) return 0.0;

        // Meta-fitness is high if the state is NOT trivial (not all zeros, not all ones)
        // and SCI (Simulation Complexity Index) is high.
        $snapshot = $res['snapshot'] ?? [];
        $sci = $snapshot['sci'] ?? 0.0;
        $entropy = $snapshot['entropy'] ?? 0.5;

        // We want high complexity (SCI) but sustainable entropy (near 0.5)
        return $sci + (0.5 - abs(0.5 - $entropy));
    }
}
