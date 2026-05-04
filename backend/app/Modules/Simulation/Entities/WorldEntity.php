<?php

namespace App\Modules\Simulation\Entities;

/**
 * WorldEntity — Domain object cho World, tách biệt khỏi Eloquent Model.
 * 
 * Chứa toàn bộ state cần thiết cho Domain Logic mà không phụ thuộc vào ORM.
 */
class WorldEntity
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $multiverseId,
        public readonly string $name,
        public array $axiom,
        public array $worldSeed,
        public int $globalTick,
        public ?string $currentGenre = null,
        public ?string $baseGenre = null,
        public array $activeGenreWeights = [],
        public bool $isAutonomic = false,
        public bool $isChaotic = false,
        public int $snapshotInterval = 100,
    ) {}

    /**
     * Kiểm tra xem World có hỗ trợ một Axiom cụ thể không.
     */
    public function hasAxiom(string $key): bool
    {
        return isset($this->axiom[$key]) && $this->axiom[$key];
    }

    /**
     * Lấy giá trị Axiom, trả về default nếu không tồn tại.
     */
    public function getAxiomValue(string $key, mixed $default = null): mixed
    {
        return $this->axiom[$key] ?? $default;
    }

    /**
     * Tăng global tick.
     */
    public function advanceTick(int $count = 1): void
    {
        $this->globalTick += $count;
    }
}
