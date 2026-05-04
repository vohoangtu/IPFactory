<?php

namespace App\Modules\Simulation\Actions;

use App\Modules\Simulation\Entities\UniverseEntity;
use App\Modules\Simulation\Entities\RelicEntity;
use App\Modules\Simulation\Contracts\RelicRepositoryInterface;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Models\BranchEvent;
use Illuminate\Support\Facades\Log;

class ManifestRelicAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        private RelicRepositoryInterface $relicRepository
    ) {}

    /**
     * Kết tinh một huyền thoại thành cổ vật thực tại.
     */
    public function execute(UniverseEntity $universe, int $tick, array $relicData): RelicEntity
    {
        // 1. Khởi tạo Domain Entity
        $relicEntity = RelicEntity::createNew(
            worldId: $universe->worldId,
            originUniverseId: $universe->id,
            name: $relicData['name'],
            rarity: $relicData['rarity'],
            description: $relicData['description'],
            powerVector: $relicData['power_vector'],
            metadata: array_merge($relicData['metadata'] ?? [], [
                'manifested_at_tick' => $tick,
                'is_narrative_gem' => true
            ])
        );

        // 2. Persist thông qua Repository
        $relic = $this->relicRepository->save($relicEntity);

        // 3. Sử gia ghi chép lại huyền thoại khởi nguồn (Origin Myth)
        $narrative = $this->generateNarrative($relic, $universe);
        
        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'relic_discovery',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => $narrative
            ],
            'perceived_archive_snapshot' => [
                'relic_name' => $relic->name,
                'relic_rarity' => $relic->rarity,
                'power' => $relic->powerVector
            ]
        ]);

        // 4. Phát động sự kiện rẽ nhánh (Gây chấn động thực tại)
        BranchEvent::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'event_type' => 'relic_manifestation',
            'payload' => [
                'relic_id' => $relic->id,
                'summary' => "Huyền thoại [{$relic->name}] đã kết tinh thành thực thể vật lý.",
                'narrative' => $narrative
            ],
        ]);

        Log::info("Relic Manifested: [{$relic->name}] in Universe {$universe->id}");

        return $relic;
    }

    protected function generateNarrative(RelicEntity $relic, UniverseEntity $universe): string
    {
        $rarityLabel = strtoupper($relic->rarity);
        return "BIÊN NIÊN SỬ CỔ VẬT: Giữa những biến động của vũ trụ [{$universe->name}], một sự kiện bất hủ đã xảy ra. " .
               "Những dư âm của thực tại đã kết tụ lại tại điểm nứt gãy của Hư Không, tạo nên bảo vật [{$relic->name}] ({$rarityLabel}). " .
               "Sử gia ghi nhận: {$relic->description}";
    }
}

