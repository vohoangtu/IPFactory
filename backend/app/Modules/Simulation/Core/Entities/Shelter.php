<?php

namespace App\Modules\Simulation\Core\Entities;

/**
 * Shelter (Nhà trú ẩn) là công trình xây dựng trên Grid.
 * Cung cấp điểm An toàn (Safety) cho Agent đứng cùng Tile.
 * Nhiều Shelter gần nhau tạo thành Settlement (Làng).
 */
class Shelter
{
    public function __construct(
        public readonly string $id,
        public readonly string $ownerId,  // Agent đã xây
        public readonly int $x,
        public readonly int $y,
        public float $durability = 100.0, // HP công trình
        public readonly float $safetyBonus = 0.3  // Giảm 30% environmental threat
    ) {}

    /**
     * Thời tiết phá hủy nhà theo thời gian
     */
    public function weatherDamage(float $intensity): void
    {
        $this->durability -= $intensity * 5.0;
    }

    public function isDestroyed(): bool
    {
        return $this->durability <= 0;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->ownerId,
            'x' => $this->x, 'y' => $this->y,
            'durability' => $this->durability,
            'safety_bonus' => $this->safetyBonus,
        ];
    }
}
