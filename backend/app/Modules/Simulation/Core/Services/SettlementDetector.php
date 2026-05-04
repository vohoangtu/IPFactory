<?php

namespace App\Modules\Simulation\Core\Services;

use App\Modules\Simulation\Core\Entities\Shelter;

/**
 * Phát hiện cluster Shelter → Settlement (Làng).
 * Nếu 3+ Shelter trong bán kính 1 ô (cùng hoặc kề nhau) → Tạo Settlement.
 */
class SettlementDetector
{
    /**
     * @param Shelter[] $shelters
     * @return array<array{center: array, shelters: Shelter[], population: int}>
     */
    public function detectSettlements(array $shelters): array
    {
        $settlements = [];
        $assigned = [];

        foreach ($shelters as $i => $shelter) {
            if (isset($assigned[$i]) || $shelter->isDestroyed()) continue;

            $cluster = [$shelter];
            $assigned[$i] = true;

            // Tìm Shelter lân cận (Manhattan distance ≤ 1)
            foreach ($shelters as $j => $other) {
                if (isset($assigned[$j]) || $other->isDestroyed()) continue;
                if (abs($shelter->x - $other->x) <= 1 && abs($shelter->y - $other->y) <= 1) {
                    $cluster[] = $other;
                    $assigned[$j] = true;
                }
            }

            if (count($cluster) >= 3) {
                // Tính trung tâm Settlement
                $cx = array_sum(array_map(fn($s) => $s->x, $cluster)) / count($cluster);
                $cy = array_sum(array_map(fn($s) => $s->y, $cluster)) / count($cluster);

                $settlements[] = [
                    'center' => ['x' => round($cx), 'y' => round($cy)],
                    'shelters' => $cluster,
                    'population' => count($cluster),
                ];
            }
        }

        return $settlements;
    }
}
