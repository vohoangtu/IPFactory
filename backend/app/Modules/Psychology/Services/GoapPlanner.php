<?php

namespace App\Modules\Psychology\Services;

use App\Modules\Psychology\ValueObjects\PsychologicalState;
use Exception;

class GoapPlanner
{
    private array $availableActions;

    /**
     * @param array $availableActions Danh sách các hành động có thể (vd: từ behaviors.json có thêm mảng effects)
     */
    public function __construct(array $availableActions = [])
    {
        $this->availableActions = empty($availableActions) ? $this->defaultActions() : $availableActions;
    }

    /**
     * Dựa vào mục tiêu cao nhất (Top Goal) và Trạng thái hiện tại, lên kế hoạch gồm 1 hoặc nhiều hành động kết hợp.
     * 
     * @param PsychologicalState $state
     * @param array $topGoal Ví dụ: ['type' => 'survive', 'intensity' => 0.9]
     * @return array<string> Chuỗi hành động để đạt mục tiêu (ví dụ: ['withdraw', 'passive'])
     */
    public function planSequence(PsychologicalState $state, array $topGoal): array
    {
        $goalType = $topGoal['type'] ?? 'unknown';
        
        // Trong hệ thống thực tế dùng A* search state space. 
        // Trong Phase 3 này, ta fallback sang cơ chế Hybrid State-Machine Rules mượn ý tưởng GOAP.
        
        $sequence = [];
        
        switch ($goalType) {
            case 'survive':
                // Mục tiêu sinh tồn -> Tìm cách giảm Fear và Trauma (Danger)
                if ($state->fear > 0.8) {
                    $sequence = ['flee', 'isolate', 'passive'];
                } elseif ($state->stress > 0.7) {
                    $sequence = ['withdraw', 'rest'];
                } else {
                    $sequence = ['defend', 'resist']; // Sẵn sàng bật lại nếu nguy hiểm
                }
                break;
                
            case 'safety':
                // Mục tiêu an toàn -> Tìm cách xây dựng nguồn lực, bớt biến động
                if ($state->fear > 0.5) {
                    $sequence = ['withdraw', 'cooperate']; // Tìm bầy đàn để an toàn
                } else {
                    $sequence = ['forage', 'stockpile']; // Hoạt động kinh tế
                }
                break;
                
            case 'belonging':
                // Mục tiêu thuộc về -> Tìm kiếm trust và intimacy
                if ($state->trust < 0.3) {
                    $sequence = ['observe', 'passive']; // Cẩn trọng dò xét
                } else {
                    $sequence = ['cooperate', 'socialize', 'share']; // Chia sẻ để tạo bonding
                }
                break;
                
            case 'esteem':
                // Mục tiêu được tôn trọng -> Tìm kiếm dominance hoặc self_worth
                if ($state->selfWorth < 0.3 ?? 0.0) { // Assume State or Identity has low worth
                    $sequence = ['resist', 'prove_self', 'passive'];
                } else {
                    $sequence = ['lead', 'attack', 'dominate']; // Đủ mạnh thì đi thống trị
                }
                break;
                
            default:
                // Không có goal rõ ràng
                if ($state->stress > 0.5) {
                    $sequence = ['wander'];
                } else {
                    $sequence = ['idle'];
                }
                break;
        }

        return $sequence;
    }

    /**
     * Mock list các actions thay thế cho behaviors.json nếu chưa load được config GOAP.
     */
    private function defaultActions(): array
    {
        return [
            ['name' => 'flee', 'cost' => 1, 'preconditions' => ['in_danger' => true], 'effects' => ['fear_delta' => -0.5, 'in_danger' => false]],
            ['name' => 'isolate', 'cost' => 2, 'preconditions' => [], 'effects' => ['stress_delta' => -0.2, 'trust_delta' => -0.1]],
            ['name' => 'cooperate', 'cost' => 2, 'preconditions' => ['trust' => '>0'], 'effects' => ['trust_delta' => 0.2, 'fear_delta' => -0.1]],
            ['name' => 'attack', 'cost' => 5, 'preconditions' => ['fear' => '<0.5'], 'effects' => ['dominance_delta' => 0.5, 'anger_delta' => -0.2]],
        ];
    }
}
