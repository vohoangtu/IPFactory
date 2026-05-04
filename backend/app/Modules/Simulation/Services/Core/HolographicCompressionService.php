<?php

namespace App\Modules\Simulation\Services\Core;

use Illuminate\Support\Facades\Log;

/**
 * Phase 70: Holographic Compression Service 💎🛰️
 * 
 * "Mọi chi tiết của cả vũ trụ đều nằm trong mỗi mảnh dữ liệu."
 * Hệ thống nén dựa trên Delta-Encoding và Fast Serialization (MessagePack compatible).
 */
class HolographicCompressionService
{
    private array $baseState = [];

    /**
     * Nén State bằng phương pháp Recursive Structural Delta
     */
    public function compress(array $currentState, array $referenceBase = []): array
    {
        $startTime = microtime(true);
        
        $delta = $this->computeDelta($currentState, $referenceBase);

        $duration = (microtime(true) - $startTime) * 1000;
        
        return [
            '_hologram' => true,
            'ts' => microtime(true),
            'd' => $delta, 
            'perf' => round($duration, 3) . 'ms'
        ];
    }

    private function computeDelta(array $current, array $base): array
    {
        $delta = [];

        foreach ($current as $key => $value) {
            if (!array_key_exists($key, $base)) {
                $delta[$key] = $value;
                continue;
            }

            if (is_array($value) && is_array($base[$key])) {
                $childDelta = $this->computeDelta($value, $base[$key]);
                if (!empty($childDelta)) {
                    $delta[$key] = $childDelta;
                }
                continue;
            }

            if ($value !== $base[$key]) {
                $delta[$key] = is_float($value) ? round($value, 6) : $value;
            }
        }

        return $delta;
    }

    /**
     * Giải nén bằng Recursive Merge
     */
    public function decompress(array $hologram, array $referenceBase): array
    {
        if (!isset($hologram['_hologram'])) {
            return $hologram;
        }

        return $this->applyDelta($referenceBase, $hologram['d']);
    }

    private function applyDelta(array $base, array $delta): array
    {
        foreach ($delta as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->applyDelta($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * Phương thức nén sâu (Deep Serialization) cho Snapshots
     */
    public function persistentCompress(array $allData): string
    {
        // Sử dụng gzcompress với mức độ cân bằng (6) để ưu tiên tốc độ
        return base64_encode(gzcompress(json_encode($allData), 6));
    }

    public function persistentDecompress(string $compressed): array
    {
        return json_decode(gzuncompress(base64_decode($compressed)), true);
    }
}
