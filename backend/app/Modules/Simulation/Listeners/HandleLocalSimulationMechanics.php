<?php

namespace App\Modules\Simulation\Listeners;

use App\Modules\Simulation\Events\UniverseSimulationPulsed;
use App\Modules\Simulation\Actions\RunMicroModeAction;
use App\Modules\Simulation\Services\Meta\VoidExplorationEngine;
use App\Modules\Simulation\Services\Cosmology\EpochEngine;
use App\Modules\Simulation\Services\Core\ObservationInterferenceEngine;
use App\Modules\Simulation\Services\Meta\TrajectoryModelingEngine;
use App\Modules\Simulation\Services\Meta\ConvergenceEngine;
use App\Modules\Simulation\Services\Core\CausalCorrectionEngine;
use App\Modules\Simulation\Services\Culture\ResonanceEngine;
use App\Modules\Simulation\Services\Meta\MultiverseInteractionService;
use App\Modules\Simulation\Services\Meta\WorldRegulatorEngine;
use App\Modules\Simulation\Contracts\UniverseRepositoryInterface;
use App\Modules\Simulation\Core\Support\SimulationRandom;
use App\Modules\Institutions\Services\WorldEdictEngine;
use App\Modules\Institutions\Services\ZoneConflictEngine;
use Illuminate\Support\Facades\Log;

/**
 * HandleLocalSimulationMechanics — Phân rã từ EvaluateSimulationResult.
 * Chịu trách nhiệm về Zone Conflicts, Micro Mode, và Causal Resolution.
 */
class HandleLocalSimulationMechanics
{
    public function __construct(
        protected RunMicroModeAction $runMicroModeAction,
        protected VoidExplorationEngine $voidExplorationEngine,
        protected EpochEngine $epochEngine,
        protected ObservationInterferenceEngine $observationInterferenceEngine,
        protected TrajectoryModelingEngine $trajectoryModelingEngine,
        protected ConvergenceEngine $convergenceEngine,
        protected CausalCorrectionEngine $causalCorrectionEngine,
        protected ResonanceEngine $resonanceEngine,
        protected MultiverseInteractionService $multiverseInteractionService,
        protected WorldRegulatorEngine $worldRegulatorEngine,
        protected WorldEdictEngine $worldEdictEngine,
        protected ZoneConflictEngine $zoneConflictEngine,
        protected UniverseRepositoryInterface $simulationUniverseRepository,
    ) {}

    public function handle(UniverseSimulationPulsed $event): void
    {
        $universe = $event->universe;
        $snapshot = $event->snapshot;
        $rng = new SimulationRandom((int) ($universe->seed ?? 0), (int) $snapshot->tick, 0);
        $decisionData = $event->engineResponse['decision'] ?? [];

        try {
            // 1. Local Simulation Logic
            $this->zoneConflictEngine->resolveConflicts($universe, $snapshot, $rng);
            $this->runMicroModeAction->execute($universe, $snapshot, $decisionData);

            // 2. Specialized Engines
            $universeEntity = $this->simulationUniverseRepository->findById($universe->id);
            if ($universeEntity) {
                $this->voidExplorationEngine->process($universeEntity, (int)$snapshot->tick);
                $this->epochEngine->process($universeEntity, $snapshot);
                
                $isBeingObserved = $universe->last_observed_at && 
                                   $universe->last_observed_at->diffInSeconds(now()) < 30;
                $this->observationInterferenceEngine->process($universeEntity, (int)$snapshot->tick, $isBeingObserved);
                $this->trajectoryModelingEngine->process($universeEntity, (int)$snapshot->tick);
            }

            // 3. Causal & Resonance
            $this->convergenceEngine->process($universe, (int)$snapshot->tick);
            $this->causalCorrectionEngine->process($universe, $snapshot);
            $this->resonanceEngine->process($universe, $snapshot);

            // 4. Governance & Multiverse
            $this->worldEdictEngine->decree($universe, $snapshot);
            $this->multiverseInteractionService->detectResonance($universe);
            if ($universe->world) {
                $this->worldRegulatorEngine->process($universe->world);
            }

        } catch (\Throwable $e) {
            Log::error("HandleLocalSimulationMechanics failed: " . $e->getMessage(), [
                'universe_id' => $universe->id,
                'tick' => $snapshot->tick
            ]);
        }
    }
}
