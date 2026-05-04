<?php

namespace App\Modules\World\Entities;

use App\Modules\World\ValueObjects\Item;
use InvalidArgumentException;

class Inventory
{
    /** @var array<string, Item> */
    private array $items = [];

    public function __construct(
        public readonly string $actorId,
        public readonly float $maxWeightCapacity = 50.0 
    ) {
    }

    public function addItem(Item $newItem): bool
    {
        if ($this->getCurrentWeight() + $newItem->getTotalWeight() > $this->maxWeightCapacity) {
            return false;
        }

        foreach ($this->items as $id => $existingItem) {
            if ($existingItem->category === $newItem->category && $existingItem->decayRatePerTick === $newItem->decayRatePerTick) {
                $existingItem->mergeWith($newItem);
                return true;
            }
        }

        $this->items[$newItem->id] = $newItem;
        return true;
    }

    public function takeItemByCategory(string $category, float $amount): float
    {
        $taken = 0.0;
        $remainingNeeded = $amount;

        foreach ($this->items as $id => $item) {
            if ($item->category === $category && $item->quantity > 0) {
                $consumeAmount = $item->consume($remainingNeeded);
                $taken += $consumeAmount;
                $remainingNeeded -= $consumeAmount;

                if ($item->quantity <= 0) {
                    unset($this->items[$id]);
                }

                if ($remainingNeeded <= 0) {
                    break;
                }
            }
        }

        return $taken;
    }

    public function getCurrentWeight(): float
    {
        $weight = 0.0;
        foreach ($this->items as $item) {
            $weight += $item->getTotalWeight();
        }
        return $weight;
    }

    public function getCategoryTotal(string $category): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            if ($item->category === $category) {
                $total += $item->quantity;
            }
        }
        return $total;
    }

    public function tickDecay(): void
    {
        foreach ($this->items as $id => $item) {
            $item->age();
            if ($item->quantity <= 0) {
                unset($this->items[$id]);
            }
        }
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function toArray(): array
    {
        return [
            'actor_id' => $this->actorId,
            'max_capacity' => $this->maxWeightCapacity,
            'current_weight' => $this->getCurrentWeight(),
            'items' => array_map(fn(Item $i) => $i->toArray(), $this->items)
        ];
    }
}
