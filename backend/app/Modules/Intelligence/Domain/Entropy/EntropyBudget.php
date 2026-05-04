<?php

namespace App\Modules\Intelligence\Domain\Entropy;

final class EntropyBudget
{
    private float $remaining;

    public function __construct(float $globalEntropy, int $actorCount)
    {
        // Distribute entropy equitably
        $this->remaining = $globalEntropy / max(1, $actorCount);
    }

    /**
     * Consumes entropy from the budget.
     * @param float $amount The desired amount of entropy to consume.
     * @return float The actual amount consumed (might be less if budget is lower).
     */
    public function consume(float $amount): float
    {
        $consumable = min($this->remaining, $amount);
        $this->remaining -= $consumable;
        return $consumable;
    }

    /**
     * Check how much entropy remains in the budget.
     */
    public function remaining(): float
    {
        return $this->remaining;
    }
}
