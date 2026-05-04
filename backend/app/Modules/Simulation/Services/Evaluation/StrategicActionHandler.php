<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Services\Evaluation;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Actions\ForkUniverseAction;
use App\Modules\Simulation\Actions\TimelineMergeAction;
use App\Modules\Simulation\Contracts\UniverseRepositoryInterface;
use App\Modules\Simulation\Services\Core\ImplicitOrchestratorService;
use Illuminate\Support\Facades\Log;

class StrategicActionHandler
{
    public function __construct(
        protected ForkUniverseAction $forkUniverseAction,
        protected TimelineMergeAction $timelineMergeAction,
        protected ImplicitOrchestratorService $orchestrator,
        protected UniverseRepositoryInterface $universeRepository,
    ) {
    }

    public function handleMerge($universe, array $decision): void
    {
        $candidateId = $decision['meta']['merge_candidate_universe_id'] ?? null;
        if ($candidateId === null || (int) $candidateId === (int) $universe->id) {
            return;
        }
        try {
            $this->timelineMergeAction->execute((int) $universe->id, (int) $candidateId);
            $this->universeRepository->update($universe->id, ['status' => 'archived']);
            $this->universeRepository->update((int) $candidateId, ['status' => 'archived']);
        } catch (\Throwable $e) {
            Log::warning('EvaluateSimulationResult: merge failed: '.$e->getMessage());
        }
    }

    public function handlePromote($universe, array $decision): void
    {
        $this->universeRepository->update($universe->id, ['status' => 'promoted']);
    }

    public function handleFork($universe, int $tick, array $decision): void
    {
        $saga = $this->orchestrator->ensureSaga($universe);
        if (! $saga) {
            return;
        }

        $activeCount = Universe::where('saga_id', $saga->id)
            ->where('status', 'active')
            ->count();

        $childUniverses = $this->forkUniverseAction->execute($universe, $tick, $decision);

        if ($childUniverses->isNotEmpty() && $activeCount >= 1) {
            $this->universeRepository->update($universe->id, ['status' => 'halted']);
        }
    }

    public function applySelectivePressure($universe, $snapshot, array $decisionData): void
    {
        if (! empty($decisionData['meta']['mutation_suggestion'])) {
            $suggestion = $decisionData['meta']['mutation_suggestion'];
            $vec = $universe->state_vector ?? [];
            if (empty($vec)) {
                $vec = $snapshot->state_vector ?? [];
            }

            $updated = false;

            if (isset($suggestion['suggest_reduce_entropy'])) {
                $vec['pressure_entropy_reduction'] = true;
                $updated = true;
            }

            if (isset($suggestion['add_scar'])) {
                $scars = $vec['scars'] ?? [];
                $newScar = $suggestion['add_scar'];
                if (! in_array($newScar, $scars)) {
                    $scars[] = $newScar;
                    $vec['scars'] = $scars;
                    $updated = true;
                }
            }

            if ($updated) {
                $this->universeRepository->update($universe->id, ['state_vector' => $vec]);
            }
        }
    }
}
