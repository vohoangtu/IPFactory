<?php

namespace App\Modules\Psychology\Services;

use App\Modules\Psychology\ValueObjects\Myth;
use Illuminate\Support\Str;

class MythGenerator
{
    /**
     * Xác định xem một hệ quả thảm khốc/hoặc quá tốt đẹp của WordState có biến thành Myth hay không.
     * 
     * @param array $zoneMetrics (fear, trauma, survival_danger, entropy...)
     * @param array $zoneNarrativeEvents Danh sách các string event ID đã xảy ra trong tick này
     * @param int $currentTick
     * @return Myth|null Trả về Myth object nếu đủ điều kiện, null nếu sự kiện quá bình thường.
     */
    public function evaluateFromZoneMetrics(array $zoneMetrics, array $zoneNarrativeEvents, int $currentTick): ?Myth
    {
        $fear = $zoneMetrics['fear'] ?? 0.0;
        $trauma = $zoneMetrics['trauma'] ?? 0.0;
        $entropy = $zoneMetrics['entropy'] ?? 0.0;

        // Điều kiện 1: Thảm họa / Đại biến cố dẫn tới nỗi sợ tập thể cực độ
        if ($fear > 0.8 && $entropy > 0.8 && $trauma > 0.5) {
            // Lấy signature nổi bật nhất từ narrative events
            $signature = $this->determineSignature($zoneNarrativeEvents, 'catastrophe');
            
            return new Myth(
                id: (string) Str::uuid(),
                eventSignature: $signature,
                narrativePower: 1.0,       // Sự kiện nổ tung gây sốc -> Belief tuyệt đối ngay lúc đầu
                distortionFactor: 0.0,
                traumaImprint: $fear,      // In hằn sự sợ hãi (vd: sợ Lửa, Bạch tuộc khổng lồ,...)
                moraleImprint: 0.0,
                creationTick: $currentTick
            );
        }

        // Điều kiện 2: Kỳ tích / Cứu rỗi (Fear cực thấp, Trauma giảm, Entropy rớt mạnh)
        $stability = $zoneMetrics['stability'] ?? 0.0;
        if ($stability > 0.9 && $fear < 0.1 && $trauma < 0.2) {
            $signature = $this->determineSignature($zoneNarrativeEvents, 'miracle');
            
            return new Myth(
                id: (string) Str::uuid(),
                eventSignature: $signature,
                narrativePower: 0.9,
                distortionFactor: 0.1, // Kỳ tích thường bị phóng đại ngay từ đầu
                traumaImprint: 0.0,
                moraleImprint: $stability, // Tạo hy vọng / Inspiration di truyền
                creationTick: $currentTick
            );
        }

        return null; // Quá bình thường, không ai thèm nhớ
    }

    private function determineSignature(array $events, string $fallback): string
    {
        if (empty($events)) {
            return $fallback . '_' . uniqid();
        }

        // Ưu tiên các event có key quan trọng
        foreach ($events as $e) {
            if (str_contains(strtolower($e), 'dragon') || str_contains(strtolower($e), 'god') || str_contains(strtolower($e), 'eruption')) {
                return $e;
            }
        }

        return $events[0];
    }
}
