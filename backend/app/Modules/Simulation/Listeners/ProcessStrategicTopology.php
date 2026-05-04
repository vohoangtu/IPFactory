<?php

namespace App\Modules\Simulation\Listeners;

use App\Modules\World\Models\Universe;

use App\Modules\Simulation\Events\UniverseSimulationPulsed;
use App\Modules\Intelligence\Actions\DecideUniverseAction;
use App\Modules\Simulation\Actions\ForkUniverseAction;
use App\Modules\Simulation\Actions\TimelineMergeAction;
use App\Modules\Simulation\Repositories\UniverseRepository;
use App\Modules\Simulation\Services\Core\ImplicitOrchestratorService;
use Illuminate\Support\Facades\Log;

/**
 * ProcessStrategicTopology — Phân rã từ EvaluateSimulationResult.
 * Chịu trách nhiệm thực hiện các quyết định Fork, Merge, Promote, Archive.
 */
class ProcessStrategicTopology
{
    public function __construct(
        protected DecideUniverseAction $decideUniverseAction,
        protected ForkUniverseAction $forkUniverseAction,
        protected TimelineMergeAction $timelineMergeAction,
        protected ImplicitOrchestratorService $orchestrator,
        protected UniverseRepository $universeRepository,
    ) {}

    public function handle(UniverseSimulationPulsed $event): void
    {
        $universe = $event->universe;
        $snapshot = $event->snapshot;

        try {
            // 1. Strategic Decision via AI
            $decisionData = $this->decideUniverseAction->execute($snapshot);
            $action = $decisionData['action'] ?? 'continue';

            // 2. Execute Strategic Actions
            switch ($action) {
                case 'fork':
                    $this->handleFork($universe, (int)$snapshot->tick, $decisionData);
                    break;
                case 'merge':
                    $this->handleMerge($universe, $decisionData);
                    break;
                case 'promote':
                    $this->universeRepository->update($universe->id, ['status' => 'promoted']);
                    break;
                case 'archive':
                    $this->handleArchive($universe, (int)$snapshot->tick);
                    break;
                case 'mutate':
                case 'continue':
                    $this->applySelectivePressure($universe, $snapshot, $decisionData);
                    break;
            }

        } catch (\Throwable $e) {
            Log::error("ProcessStrategicTopology failed: " . $e->getMessage(), [
                'universe_id' => $universe->id,
                'tick' => $snapshot->tick
            ]);
        }
    }

    protected function handleMerge($universe, array $decision): void
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
            Log::warning("ProcessStrategicTopology: merge failed: " . $e->getMessage());
        }
    }

    protected function handleFork($universe, int $tick, array $decision): void
    {
        $saga = $this->orchestrator->ensureSaga($universe);
        if (!$saga) return;

        $activeCount = \App\Modules\Simulation\Models\Universe::where('saga_id', $saga->id)
            ->where('status', 'active')
            ->count();

        $childUniverses = $this->forkUniverseAction->execute($universe, $tick, $decision);

        if ($childUniverses->isNotEmpty() && $activeCount >= 1) {
            $this->universeRepository->update($universe->id, ['status' => 'halted']);
        }
    }

    protected function handleArchive($universe, int $tick): void
    {
        $minTicks = (int) config('worldos.autonomic.min_ticks_before_archive', 150);
        $forkGracePeriod = (int) config('worldos.autonomic.fork_grace_period_ticks', 50);
        
        $inGracePeriod = $universe->forked_at_tick !== null
            && ($tick - (int) $universe->forked_at_tick) < $forkGracePeriod;

        if ($tick >= $minTicks && !$inGracePeriod) {
            $this->universeRepository->update($universe->id, ['status' => 'archived']);
        }
    }

    protected function applySelectivePressure($universe, $snapshot, array $decisionData): void
    {
        if (!empty($decisionData['meta']['mutation_suggestion'])) {
            $suggestion = $decisionData['meta']['mutation_suggestion'];
            $vec = $universe->state_vector ?? $snapshot->state_vector ?? [];
            $updated = false;

            if (isset($suggestion['suggest_reduce_entropy'])) {
                $vec['pressure_entropy_reduction'] = true;
                $updated = true;
            }

            if (isset($suggestion['add_scar'])) {
                $scars = $vec['scars'] ?? [];
                $newScar = $suggestion['add_scar'];
                if (!in_array($newScar, $scars)) {
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
