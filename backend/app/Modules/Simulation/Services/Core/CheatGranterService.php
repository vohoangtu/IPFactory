<?php

namespace App\Modules\Simulation\Services\Core;

use App\Modules\Intelligence\Models\LegendaryAgent;

/**
 * CheatGranterService: Provides "Golden Fingers" to Isekai agents (§V26).
 */
class CheatGranterService
{
    /**
     * Grant a cheat ability to an agent being transmigrated.
     */
    public function grantCheat(LegendaryAgent $agent): string
    {
        $cheats = [
            'The Omniscient Eye (Con Mắt Thấu Thị)' => 'Giúp chủ nhân nhìn thấu mọi quy luật Axiom của vũ trụ mới.',
            'The Gluttony System (Hệ thống Thôn Phệ)' => 'Hấp thụ điểm Order và Entropy từ kẻ thù đã đánh bại.',
            'Plot Armor (Hào Quang Nhân Vật Chính)' => 'Miễn nhiễm với mọi sự trừng phạt từ Celestial Antibody Engine.',
            'Absolute Replication (Sao Chép Tuyệt Đối)' => 'Tự động sao chép kỹ năng của Demiurge cai quản khu vực.',
            'The Fourth Wall Breaker (Kẻ Phá Vỡ Tòa Tháp)' => 'Nhận thức được The Master Clock và can thiệp thẳng vào hệ thống.'
        ];

        $prng = \App\Modules\Simulation\Services\Ecology\SimulationPRNG::forUniverse($universe);
        $cheatName = $prng->arrayRand($cheats);
        
        $tags = $agent->fate_tags ?? [];
        $tags[] = "Cheat: {$cheatName}";
        
        // Also ensure they are transcendental so they can survive future collapses
        $agent->is_transcendental = true;
        $agent->fate_tags = $tags;
        
        // Slightly increase their heresy since they don't belong here
        $agent->heresy_score = min(1.0, ($agent->heresy_score ?? 0) + 0.1); 
        
        $agent->save();

        return $cheatName;
    }
}



