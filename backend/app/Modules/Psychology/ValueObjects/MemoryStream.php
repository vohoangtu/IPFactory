<?php

namespace App\Modules\Psychology\ValueObjects;

/**
 * MemoryStream – ring buffer of recent memories (max 20 items).
 * Older memories are automatically evicted when full.
 *
 * Acts as the short-term episodic memory for an actor/zone aggregate.
 * Decays all weights every tick. Provides bias analysis for MeaningEngine.
 */
final class MemoryStream
{
    private const MAX_SIZE = 20;

    /** @var MemoryItem[] */
    private array $items = [];

    public function push(MemoryItem $item): void
    {
        $this->items[] = $item;
        if (count($this->items) > self::MAX_SIZE) {
            array_shift($this->items); // evict oldest
        }
    }

    /**
     * Decay all memory weights each tick. Call once per tick.
     */
    public function decayAll(float $rate = 0.97): void
    {
        foreach ($this->items as $item) {
            $item->decay($rate);
        }
        // Prune memories that have effectively vanished
        $this->items = array_values(
            array_filter($this->items, fn(MemoryItem $m) => $m->weight > 0.01)
        );
    }

    /**
     * @return MemoryItem[]
     */
    public function recent(int $n = 5): array
    {
        return array_slice(array_reverse($this->items), 0, $n);
    }

    /**
     * @return MemoryItem[]
     */
    public function filterByType(string $type): array
    {
        return array_values(
            array_filter($this->items, fn(MemoryItem $m) => $m->type === $type)
        );
    }

    /**
     * Total trauma score (weighted sum of trauma memories).
     * Used by MeaningEngine as Freudian hidden bias.
     */
    public function traumaTotal(): float
    {
        $total = 0.0;
        foreach ($this->filterByType(MemoryItem::TYPE_TRAUMA) as $m) {
            $total += $m->traumaStrength * $m->weight;
        }
        foreach ($this->filterByType(MemoryItem::TYPE_BETRAYAL) as $m) {
            $total += $m->intensity * $m->weight * 0.7;
        }
        return min(1.0, $total);
    }

    /**
     * Average valence of N most recent memories (confirmation bias signal).
     */
    public function recentBias(int $n = 5): float
    {
        $recent = $this->recent($n);
        if (empty($recent)) {
            return 0.0;
        }
        $sum = array_sum(array_map(fn(MemoryItem $m) => $m->effectiveBias(), $recent));
        return $sum / count($recent);
    }

    /**
     * Effective bias including weight × valence for interpretation overlay.
     */
    public function avgValence(): float
    {
        if (empty($this->items)) {
            return 0.0;
        }
        $sum = array_sum(array_map(fn(MemoryItem $m) => $m->valence * $m->weight, $this->items));
        $totalWeight = array_sum(array_map(fn(MemoryItem $m) => $m->weight, $this->items));
        return $totalWeight > 0 ? $sum / $totalWeight : 0.0;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function all(): array
    {
        return $this->items;
    }

    public function toArray(): array
    {
        return array_map(fn(MemoryItem $m) => $m->toArray(), $this->items);
    }

    public static function empty(): self
    {
        return new self();
    }

    public static function fromArray(array $data, int $currentTick = 0): self
    {
        $stream = new self();
        foreach ($data as $item) {
            $stream->push(new MemoryItem(
                type:          $item['type']            ?? MemoryItem::TYPE_NEUTRAL,
                valence:       (float) ($item['valence']         ?? 0.0),
                intensity:     (float) ($item['intensity']       ?? 0.0),
                weight:        (float) ($item['weight']          ?? 1.0),
                isTrauma:      (bool)  ($item['is_trauma']       ?? false),
                traumaStrength:(float) ($item['trauma_strength'] ?? 0.0),
                tick:          (int)   ($item['tick']            ?? $currentTick),
            ));
        }
        return $stream;
    }
}
