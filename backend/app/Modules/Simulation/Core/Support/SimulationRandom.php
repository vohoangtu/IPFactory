<?php

namespace App\Modules\Simulation\Core\Support;

use App\Modules\Intelligence\Domain\Rng\SimulationRng;

/**
 * Seeded RNG for simulation tick. Use universe seed + tick (and optional salt) so that
 * same seed + tick always yields same sequence (deterministic, replayable).
 * Do not use rand() or mt_rand() in simulation path.
 */
final class SimulationRandom
{
    private SimulationRng $rng;

    public function __construct(int $universeSeed, int $tick, int $salt = 0)
    {
        $this->rng = new SimulationRng($universeSeed, $tick, $salt);
    }

    /** Float in [0, 1). */
    public function nextFloat(): float
    {
        return $this->rng->nextFloat();
    }

    /** Integer in [$min, $max] inclusive. */
    public function int(int $min, int $max): int
    {
        if ($max <= $min) {
            return $min;
        }
        $u = $this->rng->nextFloat();
        return $min + (int) floor($u * ($max - $min + 1));
    }

    /** Float in [$min, $max]. */
    public function float(float $min, float $max): float
    {
        return $this->rng->floatRange($min, $max);
    }

    /** Pick one key from array (deterministic). */
    public function arrayKey(array $arr): ?string
    {
        $keys = array_keys($arr);
        if (empty($keys)) {
            return null;
        }
        $idx = $this->int(0, count($keys) - 1);
        return $keys[$idx];
    }
}
