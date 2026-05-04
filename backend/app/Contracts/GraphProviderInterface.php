<?php

namespace App\Contracts;

interface GraphProviderInterface
{
    /**
     * Lấy các nút (Nodes) trong đồ thị liên quan đến một Universe.
     */
    public function getUniverseNodes(int $universeId): array;

    /**
     * Lấy các cạnh (Edges/Relationships) trong đồ thị liên quan đến một Universe.
     */
    public function getUniverseEdges(int $universeId): array;

    /**
     * Đồng bộ hóa dữ liệu trạng thái mới vào Đồ thị (nếu dùng GraphDB ngoài).
     */
    public function sync(int $universeId, array $data): bool;
}
