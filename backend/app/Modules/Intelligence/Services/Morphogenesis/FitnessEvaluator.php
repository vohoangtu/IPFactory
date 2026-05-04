<?php

namespace App\Modules\Intelligence\Services\Morphogenesis;

use App\Contracts\SimulationEngineClientInterface;

/**
 * Evaluates the fitness of an ArchetypeGenome by running parallel simulations.
 * Higher fitness means the archetype survives longer or maintains civilization stability.
 */
class FitnessEvaluator
{
    public function __construct(
        private SimulationEngineClientInterface $engine
    ) {}

    /**
     * Evaluate a population of genomes.
     *
     * @param ArchetypeGenome[] $population
     * @param array $initialState The starting state for evaluation.
     * @param array|null $worldConfig
     * @return array<string, float> Map of genome ID => fitness score.
     */
    public function evaluatePopulation(array $population, array $initialState, ?array $worldConfig = null): array
    {
        $requests = [];
        $genomeMap = [];

        // For each genome, we run 3 short simulations to get an average fitness
        foreach ($population as $genome) {
            for ($i = 0; $i < 3; $i++) {
                $idx = count($requests);
                $genomeMap[$idx] = $genome->id;
                
                // We create a world where THIS genome is slightly favored
                // by setting the initial state to something it likes.
                $requests[] = [
                    'universe_id' => 0,
                    'ticks' => 15,
                    'state_input' => $initialState,
                    'world_config' => $worldConfig,
                    'metadata' => [
                        'favored_weights' => $genome->attractorVector,
                    ]
                ];
            }
        }

        // Run batch simulations
        $batchResult = $this->engine->batchAdvance($requests);
        $responses = $batchResult['responses'] ?? [];

        // Aggregate scores
        $scores = [];
        foreach ($population as $g) $scores[$g->id] = 0.0;

        foreach ($responses as $idx => $res) {
            $gid = $genomeMap[$idx] ?? null;
            if (!$gid) continue;

            $fitness = $this->calculateFitness($res);
            $scores[$gid] += $fitness / 3.0; // average of 3 runs
        }

        return $scores;
    }

    /**
     * Calculate fitness for a single simulation result.
     * Fitness = Stability + Progress - Entropy.
     */
    private function calculateFitness(array $result): float
    {
        if (!($result['ok'] ?? false)) return 0.0;

        $snapshot = $result['snapshot'] ?? [];
        $stability = $snapshot['stability_index'] ?? 0.5;
        $entropy = $snapshot['entropy'] ?? 0.5;
        $knowledge = $snapshot['state_vector']['knowledge'] ?? 0.0;

        // Formula: (Stability * 2) + Knowledge - (Entropy * 0.5)
        return ($stability * 2.0) + $knowledge - ($entropy * 0.5);
    }
}
