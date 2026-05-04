<?php

namespace App\Modules\Simulation\Core\Supervisor;

use App\Modules\Simulation\Core\EngineRegistry;
use Illuminate\Support\Facades\Log;
use App\Modules\World\Models\Universe as UniverseModel;
use App\Modules\Simulation\Core\Supervisor\SnapshotManager;

/**
 * Orchestrates advance flow: EngineDriver → StateSynchronizer → SnapshotManager → EventDispatcher → RuntimePipeline.
 */
final class SimulationSupervisor
{
    public function __construct(
        private readonly \App\Modules\Simulation\Contracts\UniverseRepositoryInterface $universeRepository,
        private readonly \App\Modules\Simulation\Contracts\SnapshotRepositoryInterface $snapshotRepository,
        private readonly EngineDriver $engineDriver,
        private readonly StateSynchronizer $stateSynchronizer,
        private readonly SnapshotManager $snapshotManager,
        private readonly EventDispatcher $eventDispatcher,
        private readonly RuntimePipeline $runtimePipeline,
        private readonly EngineRegistry $engineRegistry,
        private readonly \App\Modules\Simulation\Core\Runtime\EventDrivenScheduler $scheduler,
        private readonly \App\Modules\Simulation\Core\Runtime\State\StateManager $stateManager,
    ) {}

    /**
     * @return array{ok: bool, snapshot?: array, error_message?: string, ...}
     */
    public function execute(int $universeId, int $ticks): array
    {
        Log::info('Simulation: advance requested', ['universe_id' => $universeId, 'ticks' => $ticks]);

        $universe = $this->universeRepository->findById($universeId);

        if (! $universe || $universe->status === 'halted' || $universe->status === 'restarting') {
            Log::warning('Simulation: advance rejected (universe not found or halted)', ['universe_id' => $universeId]);
            return ['ok' => false, 'error_message' => 'Universe not found, is halted, or is restarting'];
        }

        $universeModel = UniverseModel::find($universeId);
        if (!$universeModel) {
            return ['ok' => false, 'error_message' => 'Universe model not found'];
        }

        $tickDurationMsTotal = 0;
        $engineResponse = ['ok' => true];

        for ($i = 0; $i < $ticks; $i++) {
            Log::info("Simulation Loop: Starting tick iteration", ['iteration' => $i, 'universe_id' => $universe->id]);
            $engineResponse = $this->engineDriver->advance($universe, 1);
            if (! ($engineResponse['ok'] ?? false)) {
                Log::error('Simulation: engine failure', ['universe_id' => $universe->id, 'error' => $engineResponse['error_message'] ?? 'unknown']);
                return $engineResponse;
            }

            $snapshotData = $engineResponse['snapshot'] ?? [];
            if (is_string($snapshotData['state_vector'] ?? null)) {
                $snapshotData['state_vector'] = json_decode($snapshotData['state_vector'], true) ?? [];
            }
            if (is_string($snapshotData['metrics'] ?? null)) {
                $snapshotData['metrics'] = json_decode($snapshotData['metrics'], true) ?? [];
            }

            $tickDurationMsPerTick = (float) ($engineResponse['_tick_duration_ms_per_tick'] ?? 0.0);
            $tickDurationMsTotal += $tickDurationMsPerTick;

            $engineManifest = $this->engineRegistry->getManifest();

            // Sync Entity & Persistence
            $this->stateSynchronizer->sync($universe, $snapshotData, 1, $engineManifest);

            // Snapshot Persistence via SnapshotManager (Unified logic)
            // Vector 7: Engine Health Monitor Tracking (§V11)
            $healthScore = max(0, min(100, 100 - (($tickDurationMsPerTick - 50) / 4.5)));
            $snapshotData['metrics']['engine_health'] = round($healthScore, 2);
            $snapshotData['metrics']['last_tick_ms'] = round($tickDurationMsPerTick, 2);

            try {
                $snapshotModel = $this->snapshotManager->persistOrVirtual(
                    $universeModel,
                    $snapshotData,
                    $tickDurationMsPerTick,
                    $engineManifest
                );

                if (!$snapshotModel) {
                    throw new \Exception("SnapshotManager failed to return a model");
                }

                // Handle both persisted (has ID) and virtual (no ID) snapshots
                if ($snapshotModel->id) {
                    $snapshotEntity = $this->snapshotRepository->findById($snapshotModel->id);
                } else {
                    // Virtual snapshot — build entity directly from model
                    $stateVector = is_array($snapshotModel->state_vector) ? $snapshotModel->state_vector : [];
                    $snapshotEntity = new \App\Modules\Simulation\Entities\SnapshotEntity(
                        id: null,
                        universeId: (int) $snapshotModel->universe_id,
                        tick: (int) $snapshotModel->tick,
                        stateVector: $stateVector,
                        entropy: (float) ($snapshotModel->entropy ?? 0),
                        metrics: is_array($snapshotModel->metrics) ? $snapshotModel->metrics : [],
                    );
                }
                if (!$snapshotEntity) {
                    throw new \Exception("Failed to load SnapshotEntity for ID: " . $snapshotModel->id);
                }

                $this->runtimePipeline->run(
                    $universe,
                    (int) ($snapshotData['tick'] ?? $universe->currentTick),
                    $snapshotEntity,
                    $engineResponse,
                    1
                );

                $engineEvents = $this->runtimePipeline->getLastEngineEvents();
                $this->eventDispatcher->dispatchPulsed($universe, $snapshotEntity, $engineResponse, 1, $tickDurationMsPerTick, $engineEvents);
            } catch (\Throwable $e) {
                Log::error('Simulation: tick loop failure', ['index' => $i, 'error' => $e->getMessage()]);
                break;
            }
        }

        return $this->handleSuccess($universe);
    }

    private function handleSuccess(\App\Modules\Simulation\Entities\UniverseEntity $universe): array
    {
        $latest = $this->snapshotRepository->findLatestByUniverse($universe->id);
        return [
            'ok' => true,
            'universe_id' => $universe->id,
            'tick' => $universe->currentTick,
            'snapshot' => $latest ? $latest->toArray() : [],
        ];
    }
}

