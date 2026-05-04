<?php

namespace App\Modules\Simulation\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Models\Actor;
use App\Modules\Institutions\Models\InstitutionalEntity;
use App\Modules\Institutions\Models\SupremeEntity;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Services\Core\ImplicitOrchestratorService;

/**
 * Merge Universe Action: Synthesizes two universes into a new Prime timeline.
 */
class MergeUniverseAction
implements \App\Contracts\ActionInterface {
    public function __construct(protected ImplicitOrchestratorService $orchestrator) {}

    /**
     * Merge $a and $b into a new child universe.
     */
    public function execute(Universe $a, Universe $b, int $tick): Universe
    {
        // 1. Create the new Prime Universe
        $payload = [
            'reason' => 'convergence',
            'source_a' => $a->id,
            'source_b' => $b->id,
            'note' => 'Hợp nhất đa vũ trụ (Universe Merger)'
        ];

        $prime = $this->orchestrator->spawnUniverse(
            $a->world,
            $a->id, // Use A as primary parent
            $a->saga_id,
            $payload
        );

        $prime->update(['status' => 'active']);

        // 2. Migrate and Synthesize Actors
        // In a real scenario, this would be complex. For V6, we copy top 50% influential actors from both.
        $this->synthesizeActors($a, $prime);
        $this->synthesizeActors($b, $prime);

        // 3. Migrate Institutions
        $this->synthesizeInstitutions($a, $prime);
        $this->synthesizeInstitutions($b, $prime);

        // 4. Migrate Supreme Entities
        $this->migrateSupremeEntities($a, $prime);
        $this->migrateSupremeEntities($b, $prime);

        // 5. Deactivate source universes
        $a->update(['status' => 'merged']);
        $b->update(['status' => 'merged']);

        Chronicle::create([
            'universe_id' => $prime->id,
            'from_tick' => 0,
            'to_tick' => 0,
            'type' => 'genesis_merger',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => "THỜI KHẮC HỢP NHẤT: Vũ trụ Prime #{$prime->id} được sinh ra từ sự giao thoa của hai thực tại song song. Lịch sử của cả hai đã được đúc kết thành một khởi đầu mới."
            ],
        ]);

        return $prime;
    }

    protected function synthesizeActors(Universe $source, Universe $target): void
    {
        $actors = Actor::where('universe_id', $source->id)->where('is_alive', true)->get();
        foreach ($actors as $actor) {
            $newActor = $actor->replicate();
            $newActor->universe_id = $target->id;
            $newActor->biography .= " [Dịch chuyển từ vũ trụ #{$source->id}]";
            $newActor->save();
        }
    }

    protected function synthesizeInstitutions(Universe $source, Universe $target): void
    {
        $insts = InstitutionalEntity::where('universe_id', $source->id)->whereNull('collapsed_at_tick')->get();
        foreach ($insts as $inst) {
            $newInst = $inst->replicate();
            $newInst->universe_id = $target->id;
            $newInst->save();
        }
    }

    protected function migrateSupremeEntities(Universe $source, Universe $target): void
    {
        $supremes = SupremeEntity::where('universe_id', $source->id)->where('status', 'active')->get();
        foreach ($supremes as $entity) {
            $newEntity = $entity->replicate();
            $newEntity->universe_id = $target->id;
            $newEntity->save();
        }
    }
}

