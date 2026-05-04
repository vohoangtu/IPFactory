<?php

namespace App\Modules\Narrative\Actions;

use App\Modules\Narrative\Entities\DemiurgeEntity;
use App\Modules\Narrative\Contracts\DemiurgeRepositoryInterface;
use App\Modules\Intelligence\Models\LegendaryAgent;
use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Contracts\ChronicleRepositoryInterface;
use App\Modules\Narrative\Entities\ChronicleEntity;
use App\Modules\Simulation\Core\Engines\Biological\CelestialAntibodyEngine;
use Illuminate\Support\Facades\Log;

/**
 * DivineInquisitionAction: Demiurges hunt down heretics (§V23).
 * Represents active warfare between the Gods and self-aware Agents.
 */
class DivineInquisitionAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected CelestialAntibodyEngine $antibodyEngine,
        protected ChronicleRepositoryInterface $chronicleRepository,
        protected DemiurgeRepositoryInterface $demiurgeRepository
    ) {}

    /**
     * Demiurge expends Essence to force an Inquisition in a world.
     */
    public function execute(Universe $universe, DemiurgeEntity $demiurge): void
    {
        $cost = 50.0; // Essence cost to trigger a targeted inquisition

        if ($demiurge->essence_pool < $cost) {
            Log::warning("DIVINE INQUISITION: Demiurge #{$demiurge->id} lacks essence ({$demiurge->essence_pool} < {$cost}) to trigger Inquisition.");
            return;
        }

        // Consume Essence
        $this->demiurgeRepository->decrementEssence($demiurge->id, $cost);

        // Find targets (agents with heresy > 0, doesn't need to be critical yet)
        $targets = LegendaryAgent::where('universe_id', $universe->id)
            ->where('heresy_score', '>', 0)
            ->get();

        if ($targets->isEmpty()) {
            // Wasted effort
            $chronicleEntity = ChronicleEntity::create([
                'universe_id' => $universe->id,
                'from_tick' => $universe->current_tick,
                'to_tick' => $universe->current_tick,
                'type' => 'divine_inquisition',
                'content' => "TÒA ÁN TỐI CAO: Demiurge [{$demiurge->name}] đã càn quét vũ trụ nhưng không tìm thấy mầm mống dị giáo nào.",
                'importance' => 0.5,
                'raw_payload' => [
                    'action' => 'legacy_event',
                    'description' => "TÒA ÁN TỐI CAO: Demiurge [{$demiurge->name}] đã càn quét vũ trụ nhưng không tìm thấy mầm mống dị giáo nào."
                ],
            ]);
            $this->chronicleRepository->save($chronicleEntity);
            return;
        }

        foreach ($targets as $target) {
            // Forcefully spike their heresy to critical levels to trigger the Antibody Engine immediately
            $target->update(['heresy_score' => 1.0]);
            
            $chronicleEntity = ChronicleEntity::create([
                'universe_id' => $universe->id,
                'from_tick' => $universe->current_tick,
                'to_tick' => $universe->current_tick,
                'type' => 'divine_inquisition',
                'content' => "TÒA ÁN TỐI CAO: Demiurge [{$demiurge->name}] đã chỉ đích danh [{$target->name}] là Dị giáo. Ánh sáng Trừng phạt bắt đầu giáng xuống.",
                'importance' => 0.7,
                'raw_payload' => [
                    'action' => 'legacy_event',
                    'description' => "TÒA ÁN TỐI CAO: Demiurge [{$demiurge->name}] đã chỉ đích danh [{$target->name}] là Dị giáo. Ánh sáng Trừng phạt bắt đầu giáng xuống."
                ],
            ]);
            $this->chronicleRepository->save($chronicleEntity);

            // Immediately call the Purge
            $this->antibodyEngine->execute($universe);
        }

        Log::info("WAR IN HEAVEN: Demiurge #{$demiurge->id} executed a Divine Inquisition in Universe #{$universe->id}, purging {$targets->count()} agents.");
    }
}


