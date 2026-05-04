<?php

namespace App\Modules\Simulation\Core\Runtime\Engines;

use App\Modules\Simulation\Core\Runtime\Causality\CausalLink;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

/**
 * Divergence Engine – The Observer / Quantum Collapser ⚛️👁️
 * 
 * Quản lý các rẽ nhánh nhân quả (Quantum Branching). Khi nhiều lựa chọn nảy sinh,
 * engine này sẽ quyết định kết quả nào trở thành "Canon" (Chính văn).
 */
class DivergenceEngine
{
    /**
     * Collapses a set of probabilistic links into a definitive set of canon links.
     * 
     * @param CausalLink[] $links
     * @param WorldState $state
     * @return CausalLink[]
     */
    public function collapse(array $links, WorldState $state): array
    {
        if (empty($links)) return [];

        $canonLinks = [];
        $stability = $state->getStabilityIndex();
        $entropy = $state->getEntropy();

        // Nhóm các link theo target để xử lý xung đột/rẽ nhánh
        $grouped = [];
        foreach ($links as $link) {
            $key = "{$link->targetType}_{$link->targetId}_{$link->relation}";
            $grouped[$key][] = $link;
        }

        foreach ($grouped as $key => $options) {
            if (count($options) === 1) {
                // Nếu chỉ có một lựa chọn, kiểm tra xác suất tự thân
                if ($this->shouldHappen($options[0], $stability, $entropy)) {
                    $canonLinks[] = $options[0];
                }
            } else {
                // Nếu có nhiều lựa chọn (Rẽ nhánh), chọn một nhánh (hoặc không nhánh nào)
                $chosen = $this->chooseCanonBranch($options, $stability, $entropy);
                if ($chosen) {
                    $canonLinks[] = $chosen;
                }
            }
        }

        return $canonLinks;
    }

    protected function shouldHappen(CausalLink $link, float $stability, float $entropy): bool
    {
        // Xác suất thực tế bị ảnh hưởng bởi độ ổn định của thế giới
        $realProb = $link->probability * ($stability * 0.8 + 0.2);
        
        // Hỗn loạn cao có thể làm tăng xác suất các sự kiện hy hữu
        if ($entropy > 0.8 && $link->probability < 0.2) {
            $realProb *= 2.0;
        }

        return (mt_rand(0, 1000) / 1000.0) <= $realProb;
    }

    /**
     * @param CausalLink[] $options
     */
    protected function chooseCanonBranch(array $options, float $stability, float $entropy): ?CausalLink
    {
        $totalWeight = 0;
        foreach ($options as $opt) {
            $totalWeight += $opt->probability;
        }

        $roll = (mt_rand(0, 1000) / 1000.0) * $totalWeight;
        $current = 0;

        foreach ($options as $opt) {
            $current += $opt->probability;
            if ($roll <= $current) {
                return $opt;
            }
        }

        return null;
    }
}
