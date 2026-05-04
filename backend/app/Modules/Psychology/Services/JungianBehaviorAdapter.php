<?php

namespace App\Modules\Psychology\Services;

class JungianBehaviorAdapter
{
    /**
     * Tiêm bias từ Archetype vào context để DSL có thể evaluate behaviors hợp lý.
     * Archetype hoạt động như một "kính lọc" làm ưu tiên hành vi tự nhiên của vai trò.
     *
     * @param array $baseContext Context thô (fear, stress, trust, trauma...)
     * @param string $archetypeName Tên Archetype (VD: 'VillageElder', 'Warlord', 'Technocrat')
     * @return array Context mới có thêm bias vars, ví dụ: 'archetype_cooperate_bias', v.v.
     */
    public function injectArchetypeBiases(array $baseContext, string $archetypeName): array
    {
        // Khởi tạo tất cả bias = 0
        $biases = [
            'archetype_withdraw_bias'  => 0.0,
            'archetype_resist_bias'    => 0.0,
            'archetype_cooperate_bias' => 0.0,
            'archetype_isolate_bias'   => 0.0,
            'archetype_passive_bias'   => 0.0,
        ];

        // Override dựa trên Archetype
        // Phase 2: hardcode mapping. Phase 3: Có thể đưa cái này vào DB/Model.
        switch ($archetypeName) {
            case 'VillageElder':
                $biases['archetype_cooperate_bias'] = 0.4;
                $biases['archetype_passive_bias']   = 0.2;
                $biases['archetype_resist_bias']    = -0.3;
                break;
            case 'Warlord':
                $biases['archetype_resist_bias']    = 0.6; // Warlord sẽ biến 'resist' thành 'attack' sau này
                $biases['archetype_withdraw_bias']  = -0.5;
                $biases['archetype_cooperate_bias'] = -0.2;
                break;
            case 'Technocrat':
                $biases['archetype_isolate_bias']   = 0.4; // Thiên về lý trí, tách biệt
                $biases['archetype_cooperate_bias'] = 0.2;
                $biases['archetype_passive_bias']   = -0.1;
                break;
            case 'Archmage':
                $biases['archetype_isolate_bias']   = 0.5;
                $biases['archetype_resist_bias']    = 0.3;
                break;
            default:
                // Archetype vô danh (Commoner)
                break;
        }

        return array_merge($baseContext, $biases);
    }
}
