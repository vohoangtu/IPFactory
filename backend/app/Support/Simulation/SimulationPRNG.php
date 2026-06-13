<?php

declare(strict_types=1);

namespace App\Support\Simulation;

use App\Modules\World\Models\Universe;

/**
 * Deterministic seeded PRNG dùng chung cho mô phỏng.
 *
 * Đây là HẠ TẦNG module-neutral (App\Support), không thuộc module Simulation:
 * nhiều module (Simulation, Intelligence, Narrative) cần sinh số ngẫu nhiên xác định.
 * Trước đây nằm ở App\Modules\Simulation\Services\Ecology gây coupling concrete chéo
 * module (Intelligence/Narrative → Simulation). Dời ra đây để phá phụ thuộc đó (P0-6).
 */
class SimulationPRNG
{
    /**
     * @var int $seed
     */
    protected int $seed;

    /**
     * @var int $counter
     */
    protected int $counter = 0;

    public function __construct(int $seed = 42)
    {
        $this->seed = $seed;
        $this->counter = 0;
    }

    /**
     * Initialize PRNG with Universe Seed.
     */
    public static function forUniverse(Universe $universe): self
    {
        $worldSeed = $universe->world?->world_seed['entropy_hash'] ?? crc32($universe->world_id . $universe->id);
        if (is_string($worldSeed)) {
            $worldSeed = crc32($worldSeed);
        }
        $seed = (int) $worldSeed + $universe->current_tick;
        return new self($seed);
    }

    /**
     * Get next pseudorandom number (0.0 to 1.0)
     */
    public function nextFloat(): float
    {
        // Xorshift implementation or simply seeded mt_rand
        mt_srand($this->seed + $this->counter);
        $this->counter++;
        return mt_rand() / mt_getrandmax();
    }

    /**
     * Get next integer between $min and $max inclusive.
     */
    public function nextInt(int $min, int $max): int
    {
        mt_srand($this->seed + $this->counter);
        $this->counter++;
        return mt_rand($min, $max);
    }

    /**
     * Pick a random key from an array deterministically.
     */
    public function arrayRand(array $array): int|string
    {
        $keys = array_keys($array);
        if (empty($keys)) {
            throw new \InvalidArgumentException("Array cannot be empty");
        }
        $index = $this->nextInt(0, count($keys) - 1);
        return $keys[$index];
    }

    /**
     * Get a random element from an array deterministically.
     */
    public function randomElement(array $array): mixed
    {
        $key = $this->arrayRand($array);
        return $array[$key];
    }
}
