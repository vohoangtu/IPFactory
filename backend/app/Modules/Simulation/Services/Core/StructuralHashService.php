<?php

namespace App\Modules\Simulation\Services\Core;

use Illuminate\Support\Facades\Log;

/**
 * Phase 71: Structural Hash Service 🧬⛓️
 * 
 * Implement Content-Addressed Storage pattern for simulation state.
 * Deduplicates identical sub-trees in the state manifold.
 */
class StructuralHashService
{
    private array $storage = [];

    /**
     * Tính toán hash cho một nhánh dữ liệu và lưu trữ nếu chưa có
     */
    public function store(array $data): string
    {
        // Sử dụng sha1 hoặc xxh3 (nếu có extension)
        $hash = sha1(serialize($data));

        if (!isset($this->storage[$hash])) {
            $this->storage[$hash] = $data;
            Log::debug("StructuralHashService: Stored new object", ['hash' => $hash]);
        }

        return $hash;
    }

    /**
     * Lấy dữ liệu từ hash
     */
    public function get(string $hash): ?array
    {
        return $this->storage[$hash] ?? null;
    }

    /**
     * Reset storage (cho mỗi tick hoặc phiên làm việc mới)
     */
    public function clear(): void
    {
        $this->storage = [];
    }
}
