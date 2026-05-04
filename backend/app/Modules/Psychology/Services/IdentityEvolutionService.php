<?php

namespace App\Modules\Psychology\Services;

use App\Modules\Psychology\ValueObjects\IdentityState;
use App\Modules\Intelligence\Entities\ActorState;
use App\Modules\Intelligence\Domain\Archetype\ArchetypeDefinition;

class IdentityEvolutionService
{
    private array $archetypeDefinitions;

    /**
     * @param array<ArchetypeDefinition> $archetypeDefinitions 
     */
    public function __construct(array $archetypeDefinitions = [])
    {
        // Trong context Laravel, các Archetypes có thể được resolve từ Intelligence Module's service,
        // hoặc được truyền vào qua constructor injection.
        // Tạm thời nhận mảng mapping Archetype Name -> Definition.
        $this->archetypeDefinitions = $archetypeDefinitions;
    }

    /**
     * Cập nhật bản ngã của Actor sau một loạt hành động.
     * 
     * @param IdentityState $identity
     * @param string $chosenBehavior Hành vi mà DecisionEngine vừa đưa ra
     * @param array $baseBehaviorScores Điểm raw của các behavior trước when goals/noise applied
     * @param string $actorArchetype Tên Archetype hiện tại của Actor (ví dụ: 'VillageElder')
     * @return IdentityState
     */
    public function evaluateBehavior(
        IdentityState $identity,
        string $chosenBehavior,
        array $baseBehaviorScores,
        string $actorArchetype
    ): IdentityState {
        // Tỷ lệ thay đổi mỗi tick
        $worthDelta = 0.0;
        $conflictDelta = 0.0;
        $alignmentDelta = 0.0;

        // 1. Phân tích Role Conflict (mâu thuẫn vai trò)
        // Nếu actor có Archetype, ta sẽ đánh giá xem behavior vừa làm có "đúng vai" không.
        // Cơ chế đơn giản lúc này: dùng DSL behavior để map.
        $archetypeCompatibility = $this->getArchetypeCompatibility($actorArchetype, $chosenBehavior);
        
        if ($archetypeCompatibility < 0) {
            // Hành động ngược Archetype -> Tăng Role Conflict mạnh, Mất tự tôn (Guilt)
            $conflictDelta += 0.05;
            $worthDelta -= 0.01;
            $alignmentDelta -= 0.02;
        } elseif ($archetypeCompatibility > 0) {
            // Hành động đúng Archetype -> Giảm Conflict, Tự tôn tăng (Pride)
            $conflictDelta -= 0.02;
            $worthDelta += 0.02;
            $alignmentDelta += 0.05;
        } else {
            // Baseline decay (thời gian trôi qua Role Conflict tự xoa dịu)
            $conflictDelta -= 0.01;
        }

        // 2. Phân tích ảnh hưởng của sự chủ động (Agency)
        // Nếu behavior được chọn là "withdraw" hoặc "passive", Self Worth bị giảm dần theo thời gian.
        if (in_array($chosenBehavior, ['withdraw', 'passive', 'submit'])) {
            $worthDelta -= 0.02;     // Cảm giác bất lực
        } else if (in_array($chosenBehavior, ['attack', 'lead', 'help', 'cooperate'])) {
            $worthDelta += 0.01;     // Cảm giác có ích / có sức mạnh
        }

        return $identity->applyDelta($worthDelta, $conflictDelta, $alignmentDelta);
    }

    /**
     * Tính toán xem một hành vi có tương thích với Archetype hay không.
     * Trong tương lai có thể kéo dữ liệu từ DSL hoặc Intelligence Engine.
     * Return: float [-1, 1] (-1: cực kỳ trái ngược, 1: rất khớp)
     */
    private function getArchetypeCompatibility(string $archetype, string $behavior): float
    {
        // Ví dụ Hardcode cho Phase 2 (Cần chuyển mapping ra config/DSL sau)
        $compatibilityMatrix = [
            'VillageElder' => [
                'cooperate' => 0.8, 'passive' => 0.5, 'withdraw' => 0.2,
                'attack' => -0.8, 'isolate' => -0.5, 'resist' => -0.2
            ],
            'Warlord' => [
                'attack' => 0.9, 'resist' => 0.6, 'isolate' => 0.1,
                'withdraw' => -0.8, 'passive' => -0.9, 'cooperate' => -0.3
            ],
            'Technocrat' => [
                'cooperate' => 0.5, 'isolate' => 0.6, 'passive' => 0.0,
                'attack' => -0.1, 'withdraw' => -0.2, 'resist' => -0.5
            ]
        ];

        return $compatibilityMatrix[$archetype][$behavior] ?? 0.0;
    }
}
