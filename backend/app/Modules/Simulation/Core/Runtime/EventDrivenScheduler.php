<?php

namespace App\Modules\Simulation\Core\Runtime;

use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

/**
 * Phase 70: Event Driven Scheduler (Performance Gating) ⏱️⚙️
 * 
 * "Vạn vật chỉ vận động khi có lý do."
 * Quyết định xem một Stage có cần chạy hay không dựa trên sự thay đổi của các chỉ số entropy/resonance.
 */
class EventDrivenScheduler
{
    private const STABILITY_THRESHOLD = 0.0001;

    /**
     * Kiểm tra xem Stage có nên chạy hay không (Gating)
     */
    public function shouldExecute(string $stageName, WorldState $state): bool
    {
        $entropy = (float) $state->get('entropy', 1.0);
        $resonance = (float) $state->get('field_resonance', 0.0);
        
        if ($entropy < self::STABILITY_THRESHOLD && $resonance < self::STABILITY_THRESHOLD) {
            if (in_array($stageName, ['SocialStage', 'EconomyStage', 'PoliticsStage'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Tính toán mức độ "đáng quan tâm" (Saliency) của thực tại.
     * Trả về giá trị từ 0 -> 1. 1 là cực kỳ quan trọng (cần chạy chi tiết).
     */
    public function calculateTimeSaliency(WorldState $state): float
    {
        $entropy = (float) $state->get('entropy', 0.5);
        $resonance = (float) $state->get('field_resonance', 0.0);
        $dataMass = (float) $state->get('cosmic.data_mass', 0.0);
        $singularityProgress = (float) $state->get('meta.zenith.singularity.progress', 0.0);

        // Các yếu tố làm thực tại trở nên "quan trọng":
        // 1. Biến động Entropy (Entropy cao hoặc thấp đột ngột)
        $entropyGap = abs($entropy - 0.5) * 2;
        
        // 2. Tương tác ý nghĩa (Resonance)
        $resonanceFactor = min(1.0, $resonance * 2);
        
        // 3. Tiến trình Singularity (Càng gần đích càng quan trọng)
        $progressFactor = $singularityProgress;

        // 4. Mật độ thông tin (Bekenstein Bound)
        $densityFactor = $dataMass > 0.8 ? 1.0 : 0.0;

        $saliency = ($entropyGap * 0.2) + ($resonanceFactor * 0.4) + ($progressFactor * 0.3) + ($densityFactor * 0.1);

        return round(max(0.1, min(1.0, $saliency)), 4);
    }

    /**
     * Tính toán số lượng ticks nên "nhảy cóc" (Skip Ticks) nếu thực tại nhàm chán.
     */
    public function getTickJump(float $saliency): int
    {
        // Nếu saliency thấp (< 0.2), có thể nhảy 5 -> 10 bước
        if ($saliency < 0.2) return 10;
        if ($saliency < 0.4) return 5;
        if ($saliency < 0.6) return 2;
        return 1; // Saliency cao: chạy từng tick một
    }

    /**
     * Tính toán thời gian nghỉ tối ưu (Tick Dilation - Giãn nở thời gian)
     */
    public function getOptimalDelay(WorldState $state): int
    {
        $dilation = (float) $state->get('cosmic.time_dilation', 0.0);
        
        // Nếu thời gian bị giãn nở (dilation > 0), nghỉ lâu hơn để CPU xử lý
        if ($dilation > 0) {
            return (int) ($dilation * 1000); // Nghỉ tối đa ~1.75s
        }

        return 0;
    }
}
