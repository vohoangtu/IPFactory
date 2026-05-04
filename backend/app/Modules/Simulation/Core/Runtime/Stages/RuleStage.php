<?php

namespace App\Modules\Simulation\Core\Runtime\Stages;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Runtime\Contracts\SimulationStageInterface;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use App\Modules\Simulation\Core\Runtime\RuleVM\DslPayload;
use App\Modules\Simulation\Core\Runtime\State\StateManager;

/**
 * RuleStage – Executes the Rule VM (DSL) as an orchestrated stage.
 * 
 * This treats the DSL rules (Axioms, Meta-rules) as a first-class simulation step,
 * allowing them to modify the world state before or after other stages.
 */
final class RuleStage implements SimulationStageInterface
{
    public function __construct(
        protected RuleVmService $ruleVmService,
        protected \App\Modules\Simulation\Core\Engines\Meta\HistoricalCycleEngine $historicalCycleEngine,
        protected \App\Modules\Intelligence\Services\InnovationEngine $innovationEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\LawEvolutionEngine $lawEngine,
        protected \App\Modules\Simulation\Services\Core\CausalCorrectionEngine $causalEngine,
        protected \App\Modules\Simulation\Services\Core\ObservationInterferenceEngine $observationEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\HistoricalScarsEngine $scarsEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\MetaAttractorEngine $metaAttractorEngine,
        protected \App\Modules\Simulation\Core\Engines\Social\CivilizationPhysicsEngine $physicsEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\CausalHistoryEngine $causalHistoryEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\ResonanceBleedingEngine $resonanceEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\DynamicLawEngine $dynamicLawEngine,
        protected \App\Modules\Simulation\Core\Engines\Physics\RealityAnchorEngine $anchorEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\DeepTimeMemoryEngine $memoryEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\CausalBridgeEngine $bridgeEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\HigherDimensionalEngine $higherDimEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\InfiniteRecursionEngine $recursionEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\IdealismEngine $idealismEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\SingularityEngine $singularityEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\InformationDensityEngine $infoDensityEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\PostApotheosisEngine $postApotheosisEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\OmegaConvergenceEngine $omegaEngine,
        protected StateManager $stateManager
    ) {}

    public function run(Universe $universe, int $tick, ?UniverseSnapshot $savedSnapshot = null, array $context = []): void
    {
        // Skip if simulation is collapsed
        if ($universe->status === 'collapsed') {
            return;
        }

        // 1. Evaluate DSL Rules (Axioms, History, etc.)
        $this->ruleVmService->evaluateAndApply($universe, $savedSnapshot);

        // 2. Process Meta-History Cycles (Phase 42)
        $state = $this->stateManager->get();
        if ($state) {
            $ctx = new \App\Modules\Simulation\Core\Domain\TickContext((int) ($universe->id ?? 0), $tick, (int) ($universe->seed ?? 0));

            $this->historicalCycleEngine->runWithState($state, $tick);

            // Phase 48: Innovation & Stagnation
            $this->innovationEngine->runWithState($state, $tick);

            // Phase 48: Law Evolution (Leadership)
            $this->lawEngine->handle($state, $ctx);

            // Phase 48: Causal Integrity (Overlords Rebalancing)
            $this->causalEngine->runWithState($state, $tick);

            // Phase 49: Quantum Observer & Wavefunction Collapse
            $this->observationEngine->runWithState($state, $tick);

            // Phase 51: Causal Scars & Historical Momentum
            $this->scarsEngine->handle($state, $ctx);

            // Phase 54: Civilization Field Physics (V8)
            $this->physicsEngine->handle($state, $ctx);

            // Phase 55: Meta-Attractor Graph Engine (V8 Core)
            $this->metaAttractorEngine->handle($state, $ctx);
            $this->applyDslPathIfAvailable($state, $tick, 'simulation/meta_attractors');

            // Phase 55: Causal History & Reasoning Engine (V8 Core)
            $this->causalHistoryEngine->handle($state, $ctx);

            // Phase 61: Deep Time Memory (Epochal Scars)
            $this->memoryEngine->handle($state, $ctx);

            // Phase 58: Heroic Reality Anchors
            $this->anchorEngine->handle($state, $ctx);
            $this->applyDslPathIfAvailable($state, $tick, 'simulation/anchors');

            // Phase 55: Meta-Attractor Graph Engine (V8 Core) - Duplicate call preserved for logic parity
            $this->metaAttractorEngine->handle($state, $ctx);
            $this->applyDslPathIfAvailable($state, $tick, 'simulation/meta_attractors');

            // Phase 55: Civilization Field Physics Engine (V8 Core) - Duplicate call preserved for logic parity
            $this->physicsEngine->handle($state, $ctx);
            $this->applyDslPathIfAvailable($state, $tick, 'simulation/field_physics');

            // Phase 57: Dynamic Metaphysical Axioms
            $this->dynamicLawEngine->handle($state, $ctx);

            // Phase 56: Multi-Dimensional Superposition (Reality Bleeding)
            $this->resonanceEngine->handle($state, $ctx);
            $this->applyDslPathIfAvailable($state, $tick, 'multiverse/superposition');

            // Phase 62: Multiverse Causal Bridges (Traversing Realities)
            $this->bridgeEngine->handle($state, $ctx);
            $this->applyDslPathIfAvailable($state, $tick, 'multiverse/bridges');

            // Phase 63: Civilizational Meta-Observation (Post-Apotheosis)
            $this->postApotheosisEngine->handle($state, $ctx);
            $this->applyDslPathIfAvailable($state, $tick, 'simulation/ascendance');

            // Phase 64: The Omega Point Convergence (Final Convergence)
            $this->omegaEngine->handle($state, $ctx);
            $this->applyDslPathIfAvailable($state, $tick, 'multiverse/omega');
            // Phase 65: Dimensional Ascension (Hyper-reality)
            $this->higherDimEngine->handle($state, $ctx);
            $this->applyDslPathIfAvailable($state, $tick, 'simulation/hyperspace');

            // Phase 66: Infinite Recursion (The Self-Simulation Paradox)
            $this->recursionEngine->handle($state, $ctx);
            $this->applyDslPathIfAvailable($state, $tick, 'simulation/recursion');

            // Phase 67: Idealism Engine (Subjective Physics)
            $this->idealismEngine->handle($state, $ctx);
            $this->applyDslPathIfAvailable($state, $tick, 'simulation/idealism');

            // Phase 68: Singularity Engine (The Origin Point)
            $this->singularityEngine->handle($state, $ctx);
            $this->applyDslPathIfAvailable($state, $tick, 'simulation/singularity');

            // Phase 69: Terminal Horizon (Information Saturation)
            $this->infoDensityEngine->handle($state, $ctx);
            $this->applyDslPathIfAvailable($state, $tick, 'simulation/horizon');
        }
    }

    private function applyDslPathIfAvailable(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, int $tick, string $dslPath): void
    {
        $payload = $this->ruleVmService->loadDslPayload($dslPath);

        if (!$payload->isEmpty()) {
            $this->ruleVmService->evaluateAndApplyWithState($state, $payload, $tick);
        }
    }
}




