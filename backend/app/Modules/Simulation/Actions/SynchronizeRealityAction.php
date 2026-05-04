<?php

namespace App\Modules\Simulation\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Models\BranchEvent;
use Illuminate\Support\Facades\Log;

class SynchronizeRealityAction
implements \App\Contracts\ActionInterface {
    /**
     * Đồng bộ hóa thực tại giữa hai vũ trụ có cộng hưởng cao.
     */
    public function execute(Universe $u1, Universe $u2, float $resonance, int $tick): void
    {
        // 1. Ghi chép Sử gia (The Synchronicity)
        $narrative = $this->generateSynchronicityNarrative($u1, $u2, $resonance);
        
        Chronicle::create([
            'universe_id' => $u1->id,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'multiverse_synchronicity',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => $narrative
            ],
            'perceived_archive_snapshot' => [
                'linked_universe' => $u2->name,
                'resonance' => $resonance
            ]
        ]);

        // 2. Lan tỏa hiệu ứng Thiên Đạo (Axiom Bleed)
        // Giả sử lan tỏa một phần Axiom từ vũ trụ mạnh hơn sang vũ trụ yếu hơn
        $this->bleedAxioms($u1, $u2, $resonance);

        // 3. Phát động sự kiện giao thoa
        BranchEvent::create([
            'universe_id' => $u1->id,
            'from_tick' => $tick,
            'event_type' => 'reality_sync',
            'payload' => [
                'source' => $u2->name,
                'resonance' => $resonance,
                'summary' => "SỰ GIAO THOA THỰC TẠI: Cộng hưởng với {$u2->name} đạt mức nguy cấp."
            ],
        ]);

        Log::info("Reality Synchronized: [{$u1->name}] <-> [{$u2->name}] (Res: {$resonance})");
    }

    protected function generateSynchronicityNarrative(Universe $u1, Universe $u2, float $resonance): string
    {
        return "ĐỒNG BỘ HÓA SỬ THI: Ranh giới giữa [{$u1->name}] và [{$u2->name}] đang mờ dần. " .
               "Cộng hưởng đạt mức " . round($resonance * 100) . "%, khiến các sự kiện ở thực tại này " .
               "bình ổn và soi chiếu lẫn nhau như những tấm gương đa chiều.";
    }

    protected function bleedAxioms(Universe $u1, Universe $u2, float $resonance): void
    {
        // Logic lan tỏa quy luật: đồng nhất hóa một phần các chỉ số Material hoặc World Axiom
        // Đây là nơi "Đồng bộ hóa sử thi" thực sự diễn ra về mặt dữ liệu
    }
}

