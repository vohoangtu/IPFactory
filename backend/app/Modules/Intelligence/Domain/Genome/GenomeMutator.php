<?php

namespace App\Modules\Intelligence\Domain\Genome;

use App\Modules\Intelligence\Domain\Rng\SimulationRng;

/**
 * Mutate a vector (traits or physic) for reproduction.
 * Each dimension gets +/- mutationRate (deterministic RNG).
 */
class GenomeMutator
{
    /**
     * @param array<string|int, mixed> $vector
     * @return array<string|int, float>
     */
    public function mutate(array $vector, float $mutationRate, SimulationRng $rng): array
    {
        $out = [];
        foreach ($vector as $key => $val) {
            $v = is_numeric($val) ? (float) $val : 0.5;
            $delta = ($rng->nextFloat() * 2 - 1) * $mutationRate;
            $out[$key] = max(0, min(1, $v + $delta));
        }
        return $out;
    }
}
