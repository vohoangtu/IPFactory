<?php

namespace App\Modules\Simulation\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Institutions\Models\SupremeEntity;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Models\BranchEvent;
use Illuminate\Support\Facades\Log;

class TriggerScourgeAction
implements \App\Contracts\ActionInterface {
    /**
     * Triệu hồi Tai Ương để thanh tẩy Karma nợ nghiệp.
     */
    public function execute(Universe $universe, int $tick, array $scourgeData, ?SupremeEntity $target = null): void
    {
        $name = $scourgeData['name'];
        $description = $scourgeData['description'];
        
        // 1. Tạo bản ghi Sử gia (The Sentence)
        $narrative = $this->generateRetributionNarrative($name, $description, $target, $universe);
        
        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'divine_retribution',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => $narrative
            ],
            'perceived_archive_snapshot' => [
                'scourge_name' => $name,
                'target_entity' => $target ? $target->name : 'Reality itself',
                'severity' => 'CALAMITY'
            ]
        ]);

        // 2. Phát động biến cố Thiên Phạt (Sự trừng phạt vật lý)
        BranchEvent::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'event_type' => 'divine_retribution',
            'payload' => [
                'scourge_name' => $name,
                'impact' => $scourgeData['impact'] ?? [],
                'narrative' => $narrative
            ],
        ]);

        // 3. Nếu có mục tiêu, trừng phạt thực thể
        if ($target) {
            $this->applyPunishmentToEntity($target, $tick);
        }

        Log::info("Divine Retribution Triggered: [{$name}] in Universe {$universe->id}");
    }

    protected function generateRetributionNarrative(string $name, string $desc, ?SupremeEntity $target, Universe $universe): string
    {
        $targetDesc = $target ? "thực thể [{$target->name}]" : "thực tại bệ rạc của [{$universe->name}]";
        
        return "PHÁN QUYẾT THIÊN ĐẠO: Ác nghiệp tích tụ đã chạm đến giới hạn của sự bao dung. " .
               "Thiên đạo giáng xuống [{$name}] để thanh tẩy {$targetDesc}. " .
               "Sử gia ghi nhận: \"{$desc}\"";
    }

    protected function applyPunishmentToEntity(SupremeEntity $target, int $tick): void
    {
        // Giảm sức mạnh hoặc đánh rớt trạng thái
        $oldPower = $target->power_level;
        $target->power_level = max(0.1, $target->power_level - 0.3);
        $target->karma = 0; // Thanh tẩy nợ nghiệp bằng sự đau khổ
        
        $metadata = $target->karma_metadata ?? [];
        $metadata['last_retribution_tick'] = $tick;
        $metadata['punishment_history'][] = [
            'tick' => $tick,
            'power_loss' => $oldPower - $target->power_level
        ];
        
        $target->karma_metadata = $metadata;
        $target->save();
    }
}

