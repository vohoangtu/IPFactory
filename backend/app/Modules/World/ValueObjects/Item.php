<?php

namespace App\Modules\World\ValueObjects;

use InvalidArgumentException;

/**
 * Item là một loại vật thể trong thế giới World.
 * Được sử dụng trong Inventory để tiêu thụ hoặc trao đổi.
 */
class Item
{
    public function __construct(
        public readonly string $id,
        public readonly string $category,
        public float $quantity,
        public readonly float $weightPerUnit,
        public float $quality = 1.0,
        public readonly float $decayRatePerTick = 0.0
    ) {
        if ($this->quantity < 0) {
            throw new InvalidArgumentException("Item quantity cannot be negative");
        }
    }

    public function age(): void
    {
        if ($this->decayRatePerTick > 0) {
            $this->quality = max(0.0, $this->quality - $this->decayRatePerTick);
            if ($this->quality <= 0.0) {
                $this->quantity = 0.0;
            }
        }
    }

    public function getTotalWeight(): float
    {
        return $this->quantity * $this->weightPerUnit;
    }

    public function consume(float $amount): float
    {
        $amountToConsume = min($this->quantity, $amount);
        $this->quantity -= $amountToConsume;
        return $amountToConsume;
    }

    public function mergeWith(Item $other): void
    {
        if ($this->category !== $other->category) {
            throw new InvalidArgumentException("Cannot merge items of different category");
        }

        $totalQty = $this->quantity + $other->quantity;
        if ($totalQty > 0) {
            $this->quality = (($this->quality * $this->quantity) + ($other->quality * $other->quantity)) / $totalQty;
        }

        $this->quantity = $totalQty;
        $other->quantity = 0;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'quantity' => $this->quantity,
            'weight_per_unit' => $this->weightPerUnit,
            'quality' => $this->quality,
            'decay_rate' => $this->decayRatePerTick,
        ];
    }
}
