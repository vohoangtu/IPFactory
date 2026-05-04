<?php

namespace App\Modules\Simulation\Contracts;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Entities\SnapshotEntity;

/**
 * SnapshotRepositoryInterface — Contract cho persistence của UniverseSnapshot.
 */
interface SnapshotRepositoryInterface
{
    /**
     * Tìm snapshot theo ID.
     */
    public function findById(int $id): ?SnapshotEntity;

    /**
     * Tìm snapshot mới nhất (về tick) của một universe.
     */
    public function findLatestByUniverse(int $universeId): ?SnapshotEntity;

    /**
     * Tìm snapshot tại một tick cụ thể.
     */
    public function findByTick(int $universeId, int $tick): ?SnapshotEntity;

    /**
     * Lưu trữ một snapshot mới.
     */
    public function save(SnapshotEntity $snapshot): void;

    /**
     * Tạo một snapshot instance mới (chưa lưu).
     */
    public function create(array $attributes): SnapshotEntity;
}

