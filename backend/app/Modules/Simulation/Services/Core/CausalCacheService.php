<?php

namespace App\Modules\Simulation\Services\Core;

use Illuminate\Support\Facades\Cache;

/**
 * Performance Optimization: Causal Cache Service ⚡
 * 
 * Lưu trữ kết quả của các phép tính Causal phức tạp để tránh tính toán lại
 * khi state không thay đổi đáng kể.
 */
class CausalCacheService
{
    /**
     * Tạo hash từ state và nội dung DSL để làm cache key.
     */
    public function getCacheKey(array $state, string $dsl): string
    {
        // Chỉ hash những fields chính để tăng tỉ lệ hit cache
        $relevantState = [
            'tick' => $state['tick'] ?? 0,
            'entropy' => round($state['entropy'] ?? 0, 4),
            'resonance' => round($state['field_resonance'] ?? 0, 4),
        ];

        return 'causal_v1_' . md5(json_encode($relevantState) . $dsl);
    }

    public function remember(array $state, string $dsl, \Closure $callback)
    {
        $key = $this->getCacheKey($state, $dsl);
        
        // Chỉ cache nếu state đang ở trạng thái ổn định (entropy thấp)
        $entropy = $state['entropy'] ?? 1.0;
        if ($entropy > 0.9) {
            return $callback(); // Không cache khi thực tại đang hỗn loạn (Phase Transition)
        }

        // Check if cache store supports tagging
        try {
            return Cache::tags(['simulation_causality'])->remember($key, 60, $callback);
        } catch (\BadMethodCallException $e) {
            return Cache::remember($key, 60, $callback);
        }
    }

    public function clear(): void
    {
        try {
            Cache::tags(['simulation_causality'])->flush();
        } catch (\BadMethodCallException $e) {
            // Optional: for non-tag drivers, we could either do nothing or flush entirely
            // Given it's a simulation cache, we probably want it cleared.
        }
    }
}
