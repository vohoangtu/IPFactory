<?php

namespace App\Modules\Narrative\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Institutions\Models\InstitutionalEntity;
use App\Modules\Institutions\Models\SupremeEntity;
use App\Modules\Narrative\Contracts\ChronicleRepositoryInterface;
use App\Modules\Narrative\Entities\ChronicleEntity;
use App\Modules\Simulation\Core\Engines\Meta\WorldWillEngine;

/**
 * Ascension Action: Triggers the ascension of high-tier entities to Supreme status.
 */
class AscensionAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected WorldWillEngine $willEngine,
        protected ChronicleRepositoryInterface $chronicleRepository
    ) {}

    /**
     * Scan candidate institutions and actors for ascension.
     */
    public function execute(Universe $universe, int $tick): void
    {
        // 1. Institutional Ascension
        // Threshold: Legitimacy > 0.98, Capacity > 500, Min Tick > 200
        if ($tick < 200) return;

        $candidates = InstitutionalEntity::where('universe_id', $universe->id)
            ->whereNull('collapsed_at_tick')
            ->where('legitimacy', '>', 0.98)
            ->where('org_capacity', '>', 500)
            ->get();

        foreach ($candidates as $inst) {
            $this->ascendInstitution($inst, $tick);
        }

        // 2. Heroic Actor Ascension (Demi-gods)
        // Check for specific actors with very high influence (>100)
        // (Assuming influence is stored in metrics or similar)
        // This is a placeholder for future Heroic logic.
    }

    protected function ascendInstitution(InstitutionalEntity $inst, int $tick): void
    {
        // Prevent duplicate ascension
        $exists = SupremeEntity::where('universe_id', $inst->universe_id)
            ->where('name', $inst->name)
            ->exists();
        if ($exists) return;

        $alignment = $this->willEngine->calculateAlignment($inst->universe);
        $dominant = $this->willEngine->getDominantAlignment($alignment);

        $supreme = SupremeEntity::create([
            'universe_id' => $inst->universe_id,
            'name' => "Archon {$inst->name}",
            'entity_type' => 'ascended_institution',
            'domain' => $this->mapDomain($inst->entity_type, $dominant),
            'description' => "Thực thể tối cao thăng hoa từ định chế {$inst->name}. Người bảo hộ của {$dominant}.",
            'power_level' => 1.0,
            'alignment' => $alignment,
            'status' => 'active',
            'ascended_at_tick' => $tick,
        ]);

        $chronicleEntity = ChronicleEntity::create([
            'universe_id' => $inst->universe_id,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'ascension_event',
            'content' => "SỰ THĂNG HOA TỐI CAO: Định chế {$inst->name} đã vượt ngưỡng phàm trần, trở thành {$supreme->name} cai quản cõi {$supreme->domain}.",
            'importance' => 1.0,
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => "SỰ THĂNG HOA TỐI CAO: Định chế {$inst->name} đã vượt ngưỡng phàm trần, trở thành {$supreme->name} cai quản cõi {$supreme->domain}."
            ],
        ]);
        $this->chronicleRepository->save($chronicleEntity);

        // Consume origin institution? Or mark as "Divine Presence"
        // Let's keep the institution but boost its capacity as a "temple/base"
        $inst->update([
            'org_capacity' => $inst->org_capacity + 500,
            'legitimacy' => 1.0
        ]);
    }

    protected function mapDomain(string $instType, string $alignment): string
    {
        return match($alignment) {
            'spirituality' => 'Cõi Vĩnh Hằng (Eternal)',
            'hardtech' => 'Cơ Giới Đỉnh Cao (Singularity)',
            'entropy' => 'Vực Thẳm Hư Vô (The Void)',
            default => 'Hư thực chi giới',
        };
    }
}


