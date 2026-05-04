<?php

namespace App\Modules\Simulation\Contracts;

use App\Modules\Simulation\Entities\UniverseEntity;

/**
 * UniverseRepositoryInterface — Contract cho persistence của Universe.
 * 
 * Tuân thủ Repository Pattern (DDD Layer):
 * Domain Logic chỉ làm việc với UniverseEntity, không biết Eloquent.
 */
interface UniverseRepositoryInterface
{
    public function findById(int $id): ?UniverseEntity;

    public function save(UniverseEntity $universe): void;

    /**
     * Tìm tất cả Universe đang active của một World.
     * 
     * @return UniverseEntity[]
     */
    public function findActiveByWorldId(int $worldId): array;

    /**
     * Cập nhật status của Universe (active, archived, collapsed, halted).
     */
    public function updateStatus(int $id, string $status): void;
}
