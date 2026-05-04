<?php

namespace App\Modules\Narrative\Services\Strategies;

/**
 * NanoMagicStrategy: Chuyển đổi các sự kiện kĩ thuật Nano thành ngôn ngữ Ma thuật Trung cổ.
 * Đây là minh chứng cho việc tách biệt Power System (Nano) và World Setting (Medieval).
 */
class NanoMagicStrategy
{
    public function handle(array $context): array
    {
        $pulse = $context['pulse'] ?? [];
        $era = $context['era'] ?? 'medieval';
        $powerSystem = $context['power_system'] ?? '';

        // Chỉ kích hoạt nếu đúng hệ thống sức mạnh Nano-Resonance
        if ($powerSystem !== 'magitech_nano_resonance') {
            return [];
        }

        $instructions = [];

        // 1. Chuyển đổi thuật ngữ âm thanh (Resonance) thành thuật ngữ huyền bí
        $instructions[] = "Hệ thống sức mạnh hiện tại là 'Nano-Resonance'. Trong bối cảnh Trung cổ, hãy gọi các hạt Nano là 'Linh diệp' hoặc 'Bụi thánh'.";
        $instructions[] = "Các hành động sử dụng năng lượng thực chất là sự cộng hưởng phân tử, hãy mô tả chúng như những 'Lời chú' (Incantations) làm rung chuyển không khí.";

        // 2. Xử lý các chỉ số vật lý cụ thể
        if (($pulse['entropy'] ?? 0) > 0.7) {
            $instructions[] = "Entropy đang cao (>0.7): Mô tả thực tại đang bị 'Sẹo cộng hưởng' (Resonance Scars), các vật thể xung quanh bị biến dạng hoặc tan chảy một cách kỳ quái.";
        }

        if (($pulse['stabilityIndex'] ?? 1) < 0.3) {
            $instructions[] = "Độ ổn định thấp: Thế giới đang mất kiểm soát hạt Nano, hãy mô tả bầu trời đổi sang màu bạc kim và người dân sợ hãi gọi đó là 'Ngày tàn của Thần linh'.";
        }

        return [
            'additional_prompts' => $instructions,
            'vfx_hint' => 'etheric_silver_bloom'
        ];
    }
}
