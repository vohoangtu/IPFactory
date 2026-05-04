<?php

namespace App\Modules\Psychology\Services;

use App\Modules\Psychology\ValueObjects\SocialRelation;

class ReputationResolver
{
    /**
     * Tính toán danh tiếng của một Actor (Target) dựa trên tất cả các SocialRelation
     * mà những người khác (Observers) dành cho Target đó.
     * 
     * @param array<int, array<int, SocialRelation>> $allRelations Mảng relations của tất cả Actor (tạm thời load từ DB/State)
     * @param int $targetId 
     * @return array Trả về danh tiếng { 'trust_score', 'fear_score', 'dominance_score', 'intimacy_score', 'label' }
     */
    public function resolveReputation(array $allRelations, int $targetId): array
    {
        $totalTrust = 0.0;
        $totalFear = 0.0;
        $totalDominance = 0.0;
        $totalIntimacy = 0.0;

        $observerCount = 0;

        foreach ($allRelations as $observerId => $relationsOfObserver) {
            // Xem observer có quan hệ gì với target không
            if (isset($relationsOfObserver[$targetId])) {
                $rel = $relationsOfObserver[$targetId];
                $totalTrust += $rel->trust;
                $totalFear += $rel->fear;
                $totalDominance += $rel->dominancePerceived;
                $totalIntimacy += $rel->intimacy;
                $observerCount++;
            }
        }

        if ($observerCount === 0) {
            return [
                'trust_score' => 0.0,
                'fear_score' => 0.0,
                'dominance_score' => 0.0,
                'intimacy_score' => 0.0,
                'label' => 'Unknown' // Kẻ vô danh
            ];
        }

        $avgTrust = $totalTrust / $observerCount;
        $avgFear = $totalFear / $observerCount;
        $avgDominance = $totalDominance / $observerCount;
        $avgIntimacy = $totalIntimacy / $observerCount;

        return [
            'trust_score' => $avgTrust,
            'fear_score' => $avgFear,
            'dominance_score' => $avgDominance,
            'intimacy_score' => $avgIntimacy,
            'label' => $this->determineReputationLabel($avgTrust, $avgFear, $avgDominance, $avgIntimacy)
        ];
    }

    private function determineReputationLabel(float $trust, float $fear, float $dominance, float $intimacy): string
    {
        if ($fear > 0.7 && $dominance < -0.5) return 'Tyrant'; // Bị mọi người sợ và lép vế
        if ($trust > 0.7 && $intimacy > 0.5) return 'Hero'; // Đáng tin cậy và gần gũi
        if ($trust < -0.6) return 'Outcast'; // Bị tẩy chay, không đáng tin
        if ($fear > 0.5 && $trust < 0) return 'Dangerous'; // Nguy hiểm
        if ($dominance > 0.5) return 'Submissive'; // Target bị coi là kẻ yếu/chiếu dưới

        return 'Ordinary';
    }
}
