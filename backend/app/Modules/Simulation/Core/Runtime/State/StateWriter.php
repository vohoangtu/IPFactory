<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Core\Runtime\State;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Exceptions\StateWriteException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * StateWriter — Responsible for persisting simulation state to the database
 * after a tick completes.
 *
 * All write operations are wrapped in a single database transaction.
 * Dead actors are batch-deleted in a single query (fixing the legacy N+1 problem).
 *
 * Extracted from the save() path of the original StateManager.
 */
class StateWriter
{
    public function __construct(
        protected \App\Modules\Intelligence\Contracts\ActorRepositoryInterface $actorRepository,
        protected \App\Modules\Institutions\Contracts\InstitutionalRepositoryInterface $institutionalRepository,
    ) {
    }

    /**
     * Persist the current WorldState to the database within a transaction.
     *
     * @throws StateWriteException If any write operation fails (transaction is rolled back).
     */
    public function save(Universe $universe, WorldState $state): void
    {
        try {
            DB::transaction(function () use ($universe, $state) {
                $this->persistStateVector($universe, $state);
                $this->persistActors($state);
                $this->persistInstitutions($state);
            });

            Log::debug('StateWriter: Universe state, actors and institutions batch-saved', [
                'universe_id' => $universe->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('StateWriter: Failed to persist state', [
                'universe_id' => $universe->id,
                'error'       => $e->getMessage(),
            ]);

            throw new StateWriteException(
                message: "Failed to persist simulation state for universe {$universe->id}: {$e->getMessage()}",
                previous: $e,
                universeId: $universe->id,
            );
        }
    }

    /**
     * Persist the universe state_vector (scalar world data).
     */
    protected function persistStateVector(Universe $universe, WorldState $state): void
    {
        $data = $state->toArray();
        unset($data['_snapshot_metrics']);
        unset($data['ecosystem_metrics']); // Keep state_vector clean of derived metrics

        // Phase 80: Persist Resources and Ideas back to state_vector (§World-Kernel)
        $data['resources'] = array_map(fn ($r) => $r->toArray(), $state->getResourceEntities());
        $data['ideas'] = array_map(fn ($i) => $i->toArray(), $state->getIdeaEntities());

        $universe->state_vector = $data;
        $universe->save();
    }

    /**
     * Persist actors — batch-save alive, batch-delete dead.
     */
    protected function persistActors(WorldState $state): void
    {
        $allActors = $state->getActorEntities();
        $aliveActors = array_filter($allActors, fn ($a) => $a->isAlive);
        $deadActors = array_filter($allActors, fn ($a) => !$a->isAlive);

        // Persist alive actors in batch
        $this->actorRepository->saveBatch(array_values($aliveActors));

        // Batch-delete dead actors (single query instead of N+1)
        $deadActorIds = array_filter(
            array_map(fn ($a) => $a->id ? (int) $a->id : null, $deadActors),
            fn ($id) => $id !== null
        );

        if (!empty($deadActorIds)) {
            $this->actorRepository->deleteBatch($deadActorIds);

            Log::info('StateWriter: Batch-deleted dead actors from simulation.', [
                'count' => count($deadActorIds),
            ]);
        }
    }

    /**
     * Persist institutions.
     */
    protected function persistInstitutions(WorldState $state): void
    {
        foreach ($state->getInstitutionalEntities() as $inst) {
            $this->institutionalRepository->save($inst);
        }
    }
}
