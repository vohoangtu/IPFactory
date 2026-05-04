<?php

namespace App\Modules\Psychology\Services;

use App\Modules\Psychology\ValueObjects\SocialRelation;

class SocialMemoryService
{
    private const MAX_RELATIONS = 15; // Dunbar's number thu nhỏ
    private const DECAY_RATE_PER_TICK = 0.01;

    /**
     * Ghi nhận một tương tác xã hội giữa Actor A và Actor B.
     * 
     * @param array<int, SocialRelation> $currentRelations Danh sách quan hệ hiện tại của A (key = targetId)
     * @param int $targetId ID của Actor B
     * @param float $trustDelta
     * @param float $fearDelta
     * @param float $dominanceDelta
     * @param float $intimacyDelta
     * @param int $currentTick
     * @return array<int, SocialRelation> Danh sách quan hệ mới (đã update)
     */
    public function recordInteraction(
        array $currentRelations,
        int $targetId,
        float $trustDelta,
        float $fearDelta,
        float $dominanceDelta,
        float $intimacyDelta,
        int $currentTick
    ): array {
        $relation = $currentRelations[$targetId] ?? SocialRelation::neutral($targetId, $currentTick);

        // Apply decay trước khi cộng dồn tương tác mới
        $relation = $relation->decay($currentTick, self::DECAY_RATE_PER_TICK);
        
        $newRelation = $relation->applyDelta(
            $trustDelta,
            $fearDelta,
            $dominanceDelta,
            $intimacyDelta,
            $currentTick
        );

        $currentRelations[$targetId] = $newRelation;

        // Xóa những relation quá phai mờ (decayed) và duy trì graph gọn nhẹ
        return $this->pruneRelations($currentRelations);
    }

    /**
     * Cho phép thời gian trôi qua, phai mờ tất cả quan hệ.
     * Dùng khi xử lý background tick (nếu cần).
     */
    public function decayAll(array $currentRelations, int $currentTick): array
    {
        $decayed = [];
        foreach ($currentRelations as $id => $rel) {
            $decayed[$id] = $rel->decay($currentTick, self::DECAY_RATE_PER_TICK);
        }
        return $this->pruneRelations($decayed);
    }

    /**
     * Lọc giữ lại tối đa MAX_RELATIONS quan hệ mạnh nhất (cả yêu/ghét/sợ).
     * Loại bỏ những quan hệ có intensity quá thấp (< 0.05).
     */
    private function pruneRelations(array $relations): array
    {
        // Loại bỏ intensity < 0.05
        $relations = array_filter($relations, function (SocialRelation $r) {
            return $r->getIntensity() >= 0.05;
        });

        if (count($relations) <= self::MAX_RELATIONS) {
            return $relations;
        }

        // Sort theo intensity giảm dần
        uasort($relations, function (SocialRelation $a, SocialRelation $b) {
            return $b->getIntensity() <=> $a->getIntensity();
        });

        // Giữ lại top MAX_RELATIONS
        return array_slice($relations, 0, self::MAX_RELATIONS, true);
    }
}
