<?php

namespace App\Modules\World\Entities;

/**
 * NaturalResource là một tài nguyên thiên nhiên trong thế giới World.
 */
class NaturalResource
{
    public const CATEGORY_WOOD = 'wood';
    public const CATEGORY_STONE = 'stone';
    public const CATEGORY_FOOD = 'food';
    public const CATEGORY_MINERAL = 'mineral';

    public function __construct(
        public readonly string $id,
        public readonly string $category,
        public float $currentAmount,
        public readonly float $maxAmount,
        public readonly float $regenerationRatePerTick,
        public readonly float $harvestDifficulty
    ) {
        $this->currentAmount = max(0.0, min($this->maxAmount, $this->currentAmount));
    }

    public function regenerate(float $weatherMultiplier = 1.0): void
    {
        if ($this->regenerationRatePerTick <= 0 || $this->currentAmount >= $this->maxAmount) {
            return;
        }

        $actualGrowth = $this->regenerationRatePerTick * $weatherMultiplier;
        $this->currentAmount = min($this->maxAmount, $this->currentAmount + $actualGrowth);
    }

    public function harvest(float $requestedAmount): float
    {
        if ($this->currentAmount <= 0) {
            return 0.0;
        }

        $harvested = min($this->currentAmount, $requestedAmount);
        $this->currentAmount -= $harvested;

        return $harvested;
    }

    public function isDepleted(): bool
    {
        return $this->currentAmount <= 0 && $this->regenerationRatePerTick <= 0;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'current_amount' => $this->currentAmount,
            'max_amount' => $this->maxAmount,
            'regeneration_rate' => $this->regenerationRatePerTick,
            'harvest_difficulty' => $this->harvestDifficulty,
        ];
    }
}
