<?php

namespace App\Modules\Intelligence\Services\Morphogenesis;

/**
 * Orchestrates the evolutionary cycle of ArchetypeGenomes.
 * Aim: To discover "Apex Archetypes" that can escape Dark Attractors.
 */
class MorphogenesisEngine
{
    public function __construct(
        private FitnessEvaluator $evaluator,
        private EvolutionaryOperator $operator
    ) {}

    /**
     * Run the evolutionary process.
     *
     * @param int $generations
     * @param int $populationSize
     * @param array $initialState Starting conditions for tests.
     * @return ArchetypeGenome[] The final evolved population (sorted by fitness).
     */
    public function evolve(int $generations, int $populationSize, array $initialState): array
    {
        // 1. Initialize random population
        $population = $this->initializePopulation($populationSize);

        for ($gen = 0; $gen < $generations; $gen++) {
            // 2. Evaluate fitness
            $scores = $this->evaluator->evaluatePopulation($population, $initialState);
            
            // 3. Sort by fitness (descending)
            usort($population, fn($a, $b) => ($scores[$b->id] ?? 0.0) <=> ($scores[$a->id] ?? 0.0));

            // 4. Record top performing genome in metadata
            $population[0] = new ArchetypeGenome(
                $population[0]->id,
                $population[0]->name,
                $population[0]->attractorVector,
                $population[0]->impactVector,
                array_merge($population[0]->metadata, ['generation' => $gen, 'best_fitness' => $scores[$population[0]->id] ?? 0.0])
            );

            // 5. Select survivors (Elitism: keep top 20%)
            $survivorCount = max(2, (int)($populationSize * 0.2));
            $survivors = array_slice($population, 0, $survivorCount);

            // 6. Breed new generation
            $newPopulation = $survivors; // Keep survivors (elitism)
            while (count($newPopulation) < $populationSize) {
                $parent1 = $survivors[array_rand($survivors)];
                $parent2 = $survivors[array_rand($survivors)];
                
                $child = $this->operator->crossover($parent1, $parent2);
                $child = $this->operator->mutate($child, 0.1, 0.2);
                
                $newPopulation[] = $child;
            }
            
            $population = $newPopulation;
        }

        return $population;
    }

    private function initializePopulation(int $size): array
    {
        $population = [];
        $dims = ['knowledge', 'stability', 'coercion', 'entropy'];

        for ($i = 0; $i < $size; $i++) {
            $attractor = [];
            foreach ($dims as $d) $attractor[$d] = (mt_rand(-100, 100) / 100);

            $impact = [];
            foreach ($dims as $d) $impact[$d] = (mt_rand(-100, 100) / 100);

            $population[] = new ArchetypeGenome(
                uniqid('init_'),
                "Archetype-" . ($i + 1),
                $attractor,
                $impact,
                ['generation' => 0]
            );
        }

        return $population;
    }
}
