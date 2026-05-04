<?php

namespace App\Modules\Intelligence\Domain\Rng;

final class SimulationRng
{
    private int $state;

    public function __construct(int $universeSeed, int $tick, int $actorId)
    {
        // Simple 32-bit state combining seeds, crc32 generates a 32-bit int.
        // We use string concat to ensure deterministic generation.
        // The cast to int ensures we get an integer even on 64 bit systems that can hold the uint returned by crc32.
        $this->state = (int) crc32("{$universeSeed}:{$tick}:{$actorId}") | 0xDEADBEEF;
    }

    /**
     * Implements a SplitMix64 or similar lightweight deterministic RNG algorithm
     * Modified for 32 bit PHP ints or generic usage.
     * @return float A float between 0 and 1
     */
    public function nextFloat(): float
    {
        // 1. Advance state
        $this->state = (int) ($this->state + 0x9E3779B97F4A7C15);

        // 2. Mix
        $z = $this->state;
        $z = (int) (($z ^ ($z >> 30)) * 0xBF58476D1CE4E5B9);
        $z = (int) (($z ^ ($z >> 27)) * 0x94D049BB133111EB);
        $z = $z ^ ($z >> 31);

        // 3. Convert to float [0.0, 1.0)
        // We mask the value to 53 bits (the precision of a double)
        // and divide by 2^53.
        return ($z & 0x1FFFFFFFFFFFFF) / 9007199254740992;
    }

    /**
     * @param float $min
     * @param float $max
     * @return float A random float between $min and $max
     */
    public function floatRange(float $min, float $max): float
    {
        return $min + ($max - $min) * $this->nextFloat();
    }
}
