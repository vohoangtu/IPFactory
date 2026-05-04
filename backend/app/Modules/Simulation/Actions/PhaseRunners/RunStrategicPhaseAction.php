<?php
namespace App\Modules\Simulation\Actions\PhaseRunners;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Intelligence\Actions\DecideUniverseAction;
use App\Modules\Narrative\Actions\ApplyMythScarAction;
use App\Modules\Simulation\Actions\RunMicroModeAction;
use App\Modules\Simulation\Actions\ForkUniverseAction;
use App\Modules\Simulation\Actions\TimelineMergeAction;
use App\Modules\Simulation\Repositories\UniverseRepository;
use App\Modules\Simulation\Services\Core\ImplicitOrchestratorService;
use Illuminate\Support\Facades\Log;

class RunStrategicPhaseAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected DecideUniverseAction $decideUniverseAction,
        protected ApplyMythScarAction $applyMythScarAction,
        protected RunMicroModeAction $runMicroModeAction,
        protected ForkUniverseAction $forkUniverseAction,
        protected TimelineMergeAction $timelineMergeAction,
        protected ImplicitOrchestratorService $orchestrator,
        protected UniverseRepository $universeRepository
    ) {}

    /**
     * Executes the strategic phase (decide, scars, micro mode, and structural changes like merge/fork).
     * @return array The decision data payload for usage in other phases.
     */
    public function execute(Universe $universe, UniverseSnapshot $snapshot): array
    {
        // 1. Strategic Decision
        $decisionData = $this->decideUniverseAction->execute($snapshot);
        $action = $decisionData['action'] ?? 'continue';

        // 2. Apply Myth Scars
        $this->applyMythScarAction->execute($universe, $snapshot, $decisionData);

        // 3. Run Micro Mode
        $this->runMicroModeAction->execute($universe, $snapshot, $decisionData);

        // 4. Structural Actions (Fork/Archive/Mutate/Merge/Promote)
        if ($action === 'fork') {
            Log::info("Simulation Strategy: FORK Universe {$universe->id} at Tick {$snapshot->tick}");
            $this->handleFork($universe, (int)$snapshot->tick, $decisionData);
        } elseif ($action === 'merge') {
            Log::info("Simulation Strategy: MERGE Universe {$universe->id} at Tick {$snapshot->tick}");
            $this->handleMerge($universe, $decisionData);
        } elseif ($action === 'promote') {
            Log::info("Simulation Strategy: PROMOTE Universe {$universe->id} at Tick {$snapshot->tick}");
            $this->handlePromote($universe, $decisionData);
        } elseif ($action === 'continue' || $action === 'mutate') {
            $this->applySelectivePressure($universe, $snapshot, $decisionData);
        } elseif ($action === 'archive') {
            Log::info("Simulation Strategy: ARCHIVE Universe {$universe->id} at Tick {$snapshot->tick}");
            $tick = (int) ($snapshot->tick ?? 0);
            $minTicks = (int) config('worldos.autonomic.min_ticks_before_archive', 150);
            $forkGracePeriod = (int) config('worldos.autonomic.fork_grace_period_ticks', 50);
            $inGracePeriod = $universe->forked_at_tick !== null
                && ($tick - (int) $universe->forked_at_tick) < $forkGracePeriod;
            if ($tick >= $minTicks && !$inGracePeriod) {
                $this->universeRepository->update($universe->id, ['status' => 'archived']);
            }
        }

        return $decisionData;
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
            Log::warning("RunStrategicPhaseAction: merge failed: " . $e->getMessage());
        }
    }

    protected function handlePromote($universe, array $decision): void
    {
        $this->universeRepository->update($universe->id, ['status' => 'promoted']);
    }

    protected function handleFork($universe, int $tick, array $decision): void
    {
        $saga = $this->orchestrator->ensureSaga($universe);
        if (!$saga) {
            return;
        }
        $activeCount = \App\Modules\World\Models\Universe::where('saga_id', $saga->id)
            ->where('status', 'active')
            ->count();
        $childUniverses = $this->forkUniverseAction->execute($universe, $tick, $decision);
        if ($childUniverses->isNotEmpty() && $activeCount >= 1) {
            $this->universeRepository->update($universe->id, ['status' => 'halted']);
        }
    }

    protected function applySelectivePressure($universe, $snapshot, array $decisionData): void
    {
        if (!empty($decisionData['meta']['mutation_suggestion'])) {
            $suggestion = $decisionData['meta']['mutation_suggestion'];
            $vec = $universe->state_vector ?? [];
            if (empty($vec)) $vec = $snapshot->state_vector ?? [];
            
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
