<?php

namespace App\Modules\Simulation\Core\Supervisor;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Repositories\UniverseSnapshotRepository;
use App\Modules\Simulation\Core\Contracts\SnapshotArchiveInterface;
use App\Modules\Simulation\Core\EngineRegistry;
use App\Modules\Simulation\Core\Support\SnapshotLoader;
use App\Modules\Simulation\Services\Cosmology\SimulationClock;

/**
 * Persists or creates virtual snapshot after each tick.
 */
final class SnapshotManager
{
    public function __construct(
        private readonly UniverseSnapshotRepository $snapshots,
        private readonly SnapshotLoader $snapshotLoader,
        private readonly EngineRegistry $engineRegistry,
        private readonly SnapshotArchiveInterface $snapshotArchive,
        private readonly SimulationClock $clock,
    ) {}

    public function persistOrVirtual(Universe $universe, array $snapshotData, float $tickDurationMsPerTick, ?array $engineManifest = null): UniverseSnapshot
    {
        $interval = (int) ($universe->world->snapshot_interval ?? 1);
        $shouldSave = ((int) ($snapshotData['tick'] ?? 0) % $interval === 0) || ((int) ($snapshotData['tick'] ?? 0) === 0);

        if ($shouldSave) {
            $saved = $this->saveSnapshot($universe, $snapshotData, $tickDurationMsPerTick, $engineManifest);
            $this->snapshotArchive->archive($universe, $saved);

            return $saved;
        }

        return $this->makeVirtualSnapshot($universe, $snapshotData, $tickDurationMsPerTick, $engineManifest);
    }

    private function saveSnapshot(Universe $universe, array $snapshot, ?float $tickDurationMs = null, ?array $engineManifest = null): UniverseSnapshot
    {
        $stateVector = is_string($snapshot['state_vector'] ?? null)
            ? json_decode($snapshot['state_vector'], true) ?? []
            : ($snapshot['state_vector'] ?? []);

        $metrics = is_string($snapshot['metrics'] ?? null)
            ? json_decode($snapshot['metrics'], true) ?? []
            : ($snapshot['metrics'] ?? []);

        $metrics['sci'] = $snapshot['sci'] ?? null;
        $metrics['instability_gradient'] = $snapshot['instability_gradient'] ?? null;
        if ($tickDurationMs !== null) {
            $metrics['tick_duration_ms'] = round($tickDurationMs, 2);
        }
        if (is_array($engineManifest)) {
            $metrics['engine_manifest'] = $engineManifest;
        }

        $tick = (int) ($snapshot['tick'] ?? 0);
        $metrics['cycle'] = (int) floor($tick / SimulationClock::TICKS_PER_CYCLE);
        $metrics['epoch'] = (int) floor($tick / SimulationClock::TICKS_PER_EPOCH);
        $metrics['active_phases'] = $this->clock->getEligiblePhases($tick);
        $metrics['is_macro_tick'] = ($tick % SimulationClock::TICKS_PER_CYCLE === 0);

        return $this->snapshots->save($universe, [
            'tick' => $snapshot['tick'],
            'state_vector' => $stateVector,
            'entropy' => $snapshot['entropy'] ?? null,
            'stability_index' => $snapshot['stability_index'] ?? null,
            'metrics' => $metrics,
        ]);
    }

    private function makeVirtualSnapshot(Universe $universe, array $snapshotData, ?float $tickDurationMs = null, ?array $engineManifest = null): UniverseSnapshot
    {
        $stateVector = is_string($snapshotData['state_vector'] ?? null)
            ? json_decode($snapshotData['state_vector'], true) ?? []
            : ($snapshotData['state_vector'] ?? []);
        $metrics = is_string($snapshotData['metrics'] ?? null)
            ? json_decode($snapshotData['metrics'], true) ?? []
            : ($snapshotData['metrics'] ?? []);
        $metrics['sci'] = $snapshotData['sci'] ?? null;
        $metrics['instability_gradient'] = $snapshotData['instability_gradient'] ?? null;
        if ($tickDurationMs !== null) {
            $metrics['tick_duration_ms'] = round($tickDurationMs, 2);
        }
        if (is_array($engineManifest)) {
            $metrics['engine_manifest'] = $engineManifest;
        }

        $tick = (int) ($snapshotData['tick'] ?? 0);
        $metrics['cycle'] = (int) floor($tick / SimulationClock::TICKS_PER_CYCLE);
        $metrics['epoch'] = (int) floor($tick / SimulationClock::TICKS_PER_EPOCH);
        $metrics['active_phases'] = $this->clock->getEligiblePhases($tick);
        $metrics['is_macro_tick'] = ($tick % SimulationClock::TICKS_PER_CYCLE === 0);

        $snap = new UniverseSnapshot([
            'universe_id' => $universe->id,
            'tick' => $snapshotData['tick'],
            'state_vector' => $stateVector,
            'entropy' => $snapshotData['entropy'] ?? null,
            'stability_index' => $snapshotData['stability_index'] ?? null,
            'metrics' => $metrics,
        ]);
        $snap->setRelation('universe', $universe);

        return $snap;
    }
}


