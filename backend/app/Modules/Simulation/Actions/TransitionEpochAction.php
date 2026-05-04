<?php

namespace App\Modules\Simulation\Actions;

use App\Modules\Simulation\Entities\UniverseEntity;
use App\Modules\World\Models\Universe as UniverseModel;
use App\Modules\World\Models\Epoch;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Models\BranchEvent;
use App\Modules\World\Models\World;
use App\Modules\Simulation\Events\EpochTransitioned;
use Illuminate\Support\Facades\Log;

class TransitionEpochAction
implements \App\Contracts\ActionInterface {
    /**
     * Thực hiện chuyển giao kỷ nguyên vĩ mô.
     */
    public function execute(UniverseEntity $universe, Epoch $currentEpoch, int $tick, array $nextEpochData): Epoch
    {
        // 1. Kết thúc kỷ nguyên hiện tại
        $currentEpoch->update([
            'end_tick' => $tick,
            'status' => 'past'
        ]);

        // 2. Khởi tạo kỷ nguyên mới
        $nextEpoch = Epoch::create([
            'world_id' => $universe->worldId,
            'name' => $nextEpochData['name'],
            'theme' => $nextEpochData['theme'],
            'description' => $nextEpochData['description'],
            'start_tick' => $tick,
            'status' => 'active',
            'axiom_modifiers' => $nextEpochData['modifiers'] ?? []
        ]);

        // 3. Sử gia ghi chép lại Đại Biên Niên Sử (Grand Chronicle)
        $narrative = $this->generateGrandNarrative($currentEpoch, $nextEpoch, $universe);
        
        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'epoch_transition',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => $narrative
            ],
            'perceived_archive_snapshot' => [
                'old_epoch' => $currentEpoch->name,
                'new_epoch' => $nextEpoch->name,
                'transition_theme' => $nextEpoch->theme
            ]
        ]);

        // 4. Phát động biến cố Thiên Đạo (Chấn động toàn cầu)
        BranchEvent::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'event_type' => 'epoch_transition',
            'payload' => [
                'summary' => "THỜI ĐẠI MỚI BẮT ĐẦU: {$nextEpoch->name}",
                'description' => $nextEpoch->description,
                'theme' => $nextEpoch->theme
            ],
        ]);

        // 5. Cập nhật World Axiom (Nếu cần thay đổi quy luật vĩnh viễn)
        $world = World::find($universe->worldId);
        if ($world) {
            $axiom = $world->axiom ?? [];
            $axiom['current_epoch_theme'] = $nextEpoch->theme;
            $world->update([
                'axiom' => $axiom,
                'civilization_era' => $nextEpoch->theme
            ]);
        }

        Log::info("Epoch Transition Completed: [{$currentEpoch->name}] -> [{$nextEpoch->name}]");

        // Dispatch event for narrative and artifact generation
        event(new EpochTransitioned(
            UniverseModel::find($universe->id),
            $currentEpoch,
            $nextEpoch,
            $tick
        ));

        return $nextEpoch;
    }

    protected function generateGrandNarrative(Epoch $old, Epoch $new, UniverseEntity $universe): string
    {
        return "ĐẠI BIÊN NIÊN SỬ: Một trang mới của lịch sử đã lật qua. " .
               "Kỷ nguyên [{$old->name}] đã kết thúc, để lại những di sản và tàn tích không thể phai mờ. " .
               "Giờ đây, thực tại của [{$universe->name}] bước vào [{$new->name}]. " .
               "Lời tiên tri của Sử gia: \"{$new->description}\"";
    }
}

