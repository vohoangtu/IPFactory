<?php

namespace App\Modules\Simulation\Contracts;

use App\Modules\Simulation\Entities\WorldEntity;

/**
 * WorldRepositoryInterface — Contract cho persistence của World.
 * 
 * Tuân thủ Repository Pattern (DDD Layer):
 * Domain Logic chỉ làm việc với WorldEntity, không biết Eloquent.
 */
interface WorldRepositoryInterface
{
    public function findById(int $id): ?WorldEntity;

    public function findBySlug(string $slug): ?WorldEntity;

    public function save(WorldEntity $world): void;

    /**
     * Tìm hoặc tạo World theo tên, trả về Entity.
     * 
     * @param array<string, mixed> $attributes  Các field bổ sung khi tạo mới.
     */
    public function firstOrCreate(string $name, array $attributes = []): WorldEntity;
}
