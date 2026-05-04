<?php

namespace App\Modules\Intelligence\Services\Morphogenesis;

/**
 * Handles genetic operations on ArchetypeGenomes.
 */
class EvolutionaryOperator
{
    /**
     * Perform crossover between two parent genomes.
     */
    public function crossover(ArchetypeGenome $p1, ArchetypeGenome $p2): ArchetypeGenome
    {
        $id = uniqid('gen_' . date('His') . '_');
        $name = "Cross(" . $this->getAbbrev($p1->name) . "+" . $this->getAbbrev($p2->name) . ")";

        // Crossover attractor vector
        $attractor = [];
        $keys = array_unique(array_merge(array_keys($p1->attractorVector), array_keys($p2->attractorVector)));
        foreach ($keys as $k) {
            $attractor[$k] = (mt_rand(0, 1) === 0) 
                ? ($p1->attractorVector[$k] ?? 0.0) 
                : ($p2->attractorVector[$k] ?? 0.0);
        }

        // Crossover impact vector
        $impact = [];
        $keys = array_unique(array_merge(array_keys($p1->impactVector), array_keys($p2->impactVector)));
        foreach ($keys as $k) {
            $impact[$k] = (mt_rand(0, 1) === 0) 
                ? ($p1->impactVector[$k] ?? 0.0) 
                : ($p2->impactVector[$k] ?? 0.0);
        }

        return new ArchetypeGenome($id, $name, $attractor, $impact, [
            'parents' => [$p1->id, $p2->id],
            'generation' => max($p1->metadata['generation'] ?? 0, $p2->metadata['generation'] ?? 0) + 1
        ]);
    }

    /**
     * Perform mutation on a genome.
     */
    public function mutate(ArchetypeGenome $genome, float $rate = 0.1, float $magnitude = 0.2): ArchetypeGenome
    {
        $attractor = $genome->attractorVector;
        foreach ($attractor as $k => $v) {
            if ($this->shouldMutate($rate)) {
                $attractor[$k] = max(-1, min(1, $v + $this->gaussianNoise($magnitude)));
            }
        }

        $impact = $genome->impactVector;
        foreach ($impact as $k => $v) {
            if ($this->shouldMutate($rate)) {
                $impact[$k] = max(-1, min(1, $v + $this->gaussianNoise($magnitude)));
            }
        }

        return new ArchetypeGenome(
            $genome->id . '_mut',
            $genome->name . "*",
            $attractor,
            $impact,
            array_merge($genome->metadata, ['mutated' => true])
        );
    }

    private function shouldMutate(float $rate): bool
    {
        return (mt_rand(0, 1000) / 1000) < $rate;
    }

    private function gaussianNoise(float $magnitude): float
    {
        return (mt_rand(-100, 100) / 100) * $magnitude;
    }

    private function getAbbrev(string $name): string
    {
        return substr(str_replace([' ', 'gen_', 'Gen_'], '', $name), 0, 3);
    }
}
