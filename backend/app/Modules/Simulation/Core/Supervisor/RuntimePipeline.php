<?php

namespace App\Modules\Simulation\Core\Supervisor;

use App\Modules\World\Models\Universe;

use App\Modules\Simulation\Entities\UniverseEntity;
use App\Modules\Simulation\Entities\SnapshotEntity;
use App\Modules\Simulation\Core\Runtime\SimulationTickOrchestrator;
use App\Modules\Simulation\Core\Supervisor\Contracts\PostSnapshotHandlerInterface;

/**
 * Runs tick pipeline then post-snapshot handlers (LEVEL 7) when snapshot was persisted.
 */
final class RuntimePipeline
{
    /** @param iterable<PostSnapshotHandlerInterface> $postSnapshotHandlers */
    public function __construct(
        private readonly SimulationTickOrchestrator $tickOrchestrator,
        private readonly iterable $postSnapshotHandlers,
    ) {}

    public function run(UniverseEntity $universe, int $tick, SnapshotEntity $snapshot, array $engineResponse, int $ticks): void
    {
        // Vẫn cần Eloquent models cho các sub-systems chưa refactor
        $universeModel = \App\Modules\World\Models\Universe::find($universe->id);
        $snapshotModel = \App\Modules\Simulation\Models\UniverseSnapshot::find($snapshot->id);

        if ($universeModel && $snapshotModel) {
            $this->tickOrchestrator->run(
                $universeModel,
                $tick,
                $snapshotModel,
                array_merge($engineResponse, ['_ticks' => $ticks, 'snapshot' => $engineResponse['snapshot'] ?? []])
            );

            foreach ($this->postSnapshotHandlers as $handler) {
                if ($handler instanceof PostSnapshotHandlerInterface) {
                    $handler->handle($universeModel, $snapshotModel);
                }
            }
        }
    }

    /** @return array<int, mixed> */
    public function getLastEngineEvents(): array
    {
        return $this->tickOrchestrator->getLastEngineEvents();
    }
}

