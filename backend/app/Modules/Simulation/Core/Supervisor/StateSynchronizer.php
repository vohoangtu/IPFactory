<?php

namespace App\Modules\Simulation\Core\Supervisor;

use App\Contracts\Repositories\UniverseRepositoryInterface;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Services\Meta\TemporalSyncService;
use App\Modules\Simulation\Core\Contracts\StateCacheInterface;

/**
 * Syncs state from engine snapshot back to universe (temporal sync + state_vector update).
 * When state_cache driver=redis, also writes state to cache (Phase 2 §2.3).
 */
final class StateSynchronizer
{
    public function __construct(
        private readonly \App\Modules\Simulation\Contracts\UniverseRepositoryInterface $universeRepository,
        private readonly \App\Modules\Simulation\Contracts\WorldRepositoryInterface $worldRepository,
        private readonly TemporalSyncService $temporalSync,
        private readonly StateCacheInterface $stateCache,
    ) {}

    public function sync(\App\Modules\Simulation\Entities\UniverseEntity $universe, array $snapshotData, int $ticks, ?array $engineManifest = null): void
    {
        $world = $this->worldRepository->findById($universe->worldId);
        if ($world) {
            // Chúng ta vẫn cần Model cho TemporalSyncService nếu nó chưa được refactor.
            // Trong DDD transition, đôi khi cần fetch model tạm thời ở infrastructure layer.
            $worldModel = \App\Modules\World\Models\World::find($world->id);
            if ($worldModel) {
                 $this->temporalSync->advanceGlobalClock($worldModel, $ticks);
            }
        }
        
        // Cụ thể cho Universe, TemporalSyncService:synchronize yêu cầu Model.
        $universeModel = \App\Modules\World\Models\Universe::find($universe->id);
        if ($universeModel) {
            $this->temporalSync->synchronize($universeModel);
        }

        if (is_array($engineManifest)) {
            $this->universeRepository->updateStatus($universe->id, 'active'); // Helper để update field lẻ nếu cần, hoặc dùng update chung
            // Nhưng repo chưa có updateEngineManifest. Tôi sẽ dùng Model tạm thời hoặc mở rộng Repo.
            $universeModel?->update(['engine_manifest' => $engineManifest]);
        }

        $this->syncUniverseFromSnapshotData($universe, $snapshotData);
    }

    private function syncUniverseFromSnapshotData(\App\Modules\Simulation\Entities\UniverseEntity $universe, array $snapshotData): void
    {
        $stateVector = is_string($snapshotData['state_vector'] ?? null)
            ? json_decode($snapshotData['state_vector'], true) ?? []
            : ($snapshotData['state_vector'] ?? []);

        if (! isset($stateVector['zones']) && isset($stateVector[0]['state'])) {
            $stateVector = ['zones' => $stateVector];
        }

        $stateVector['entropy'] = (float) ($snapshotData['entropy'] ?? 0.0);
        $stateVector['global_entropy'] = (float) ($snapshotData['entropy'] ?? 0.0);
        $stateVector['sci'] = (float) ($snapshotData['sci'] ?? 1.0);
        $stateVector['instability_gradient'] = (float) ($snapshotData['instability_gradient'] ?? 0.0);

        $metrics = is_string($snapshotData['metrics'] ?? null)
            ? json_decode($snapshotData['metrics'], true) ?? []
            : ($snapshotData['metrics'] ?? []);

        $stateVector['knowledge_core'] = (float) ($stateVector['knowledge_core'] ?? ($metrics['knowledge_core'] ?? 0.0));
        $stateVector['scars'] = $metrics['scars'] ?? ($stateVector['scars'] ?? []);
        $stateVector['attractors'] = is_array($stateVector['attractors'] ?? null) ? $stateVector['attractors'] : [];
        $stateVector['dark_attractors'] = is_array($stateVector['dark_attractors'] ?? null) ? $stateVector['dark_attractors'] : [];

        $existingVec = is_array($universe->stateVector) ? $universe->stateVector : [];
        $stateVector['macro_agents'] = is_array($stateVector['macro_agents'] ?? null) ? $stateVector['macro_agents'] : ($existingVec['macro_agents'] ?? []);

        $fields = null;
        if (! empty($snapshotData['global_fields'])) {
            $fields = is_string($snapshotData['global_fields'])
                ? json_decode($snapshotData['global_fields'], true)
                : $snapshotData['global_fields'];
        }
        if ($fields === null && ! empty($metrics['civ_fields'])) {
            $fields = $metrics['civ_fields'];
        }
        if (is_array($fields)) {
            $stateVector['fields'] = $fields;
        }

        if (! empty($stateVector['zones']) && is_array($stateVector['zones'])) {
            $zoneFields = [];
            foreach ($stateVector['zones'] as $idx => $zone) {
                $cf = $zone['state']['civ_fields'] ?? null;
                if (is_array($cf)) {
                    $zoneFields[$idx] = $cf;
                }
            }
            if ($zoneFields !== []) {
                $stateVector['zone_fields'] = $zoneFields;
            }
        }

        // Cập nhật Entity
        $universe->currentTick = (int) $snapshotData['tick'];
        $universe->stateVector = $stateVector;
        $universe->entropy = (float) $stateVector['entropy'];

        // Lưu qua Repository
        $this->universeRepository->save($universe);

        $tick = (int) ($snapshotData['tick'] ?? 0);
        $this->stateCache->set((int) $universe->id, $stateVector, $tick);
    }
}


