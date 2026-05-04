<?php
namespace App\Modules\Simulation\Actions\PhaseRunners;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Support\SimulationRandom;
use App\Modules\Simulation\Contracts\UniverseRepositoryInterface;
use App\Modules\Simulation\Core\Engines\Meta\AttractorEngine;
use App\Modules\Simulation\Core\Engines\Meta\DynamicAttractorEngine;
use App\Modules\Simulation\Core\Runtime\RuleVM\EventTriggerProcessor;
use App\Modules\Simulation\Services\Meta\VoidExplorationEngine;
use App\Modules\Simulation\Services\Cosmology\EpochEngine;
use App\Modules\Simulation\Services\Core\ObservationInterferenceEngine;
use App\Modules\Simulation\Services\Meta\TrajectoryModelingEngine;
use App\Modules\Simulation\Services\Meta\ConvergenceEngine;
use App\Modules\Simulation\Services\Core\CausalCorrectionEngine;
use App\Modules\Simulation\Services\Culture\ResonanceEngine;
use Illuminate\Support\Facades\Log;

class RunCosmologyPhaseAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected AttractorEngine $attractorEngine,
        protected DynamicAttractorEngine $dynamicAttractorEngine,
        protected EventTriggerProcessor $eventTriggerProcessor,
        protected UniverseRepositoryInterface $simulationUniverseRepository,
        protected VoidExplorationEngine $voidExplorationEngine,
        protected EpochEngine $epochEngine,
        protected ObservationInterferenceEngine $observationInterferenceEngine,
        protected TrajectoryModelingEngine $trajectoryModelingEngine,
        protected ConvergenceEngine $convergenceEngine,
        protected CausalCorrectionEngine $causalCorrectionEngine,
        protected ResonanceEngine $resonanceEngine
    ) {}

    /**
     * Executes the cosmology phase including attractors, rules, voids, and trajectories.
     */
    public function execute(Universe $universe, UniverseSnapshot $snapshot, SimulationRandom $rng): void
    {
        try {
            // 1. Attractor field: evaluate active attractors, persist to state_vector for event modulation
            $this->attractorEngine->evaluate($universe, $snapshot);
            $universe->refresh();

            // 2. Dynamic attractors: decay instances, spawn from rules, merge into active_attractors
            $this->dynamicAttractorEngine->process($universe, $snapshot, $rng);
            $universe->refresh();

            // 3. Event trigger processing (data-driven: rules, cooldown, probability → BranchEvent)
            $this->eventTriggerProcessor->process($universe, $snapshot, $rng);

            // 4. Cosmological Entity Processing (DDD style)
            $universeEntity = $this->simulationUniverseRepository->findById($universe->id);
            if ($universeEntity) {
                $this->voidExplorationEngine->process($universeEntity, (int)$snapshot->tick);
                $this->epochEngine->process($universeEntity, $snapshot);
                
                $isBeingObserved = $universe->last_observed_at && 
                                   $universe->last_observed_at->diffInSeconds(\Illuminate\Support\Carbon::now()) < 30;
                $this->observationInterferenceEngine->process($universeEntity, (int)$snapshot->tick, $isBeingObserved);
                $this->trajectoryModelingEngine->process($universeEntity, (int)$snapshot->tick);
            }

            // 5. Macro Physics
            $this->convergenceEngine->process($universe, (int)$snapshot->tick);
            $this->causalCorrectionEngine->process($universe, $snapshot);
            $this->resonanceEngine->process($universe, $snapshot);
            
        } catch (\Throwable $e) {
            Log::warning("RunCosmologyPhaseAction failed: " . $e->getMessage());
        }
    }
}
