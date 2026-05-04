<?php

namespace App\Modules\Simulation\Core\Runtime;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Services\Core\SimulationTracer;
use App\Modules\Simulation\Core\Runtime\Contracts\SimulationStageInterface;
use App\Modules\Simulation\Core\Runtime\Contracts\TickSchedulerInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

final class SimulationTickPipeline
{
    /** Engine events emitted during the last run() call. */
    private array $lastEngineEvents = [];

    /**
     * @param  array<string, SimulationStageInterface>  $stages  Stage key => stage instance
     */
    public function __construct(
        protected TickSchedulerInterface $scheduler,
        protected array $stages,
        protected \App\Modules\Simulation\Core\Runtime\State\StateManager $stateManager,
        protected \App\Modules\Simulation\Core\Runtime\EventDrivenScheduler $performanceScheduler,
        protected \App\Modules\Simulation\Core\Engines\Biological\AutopoieticEvolutionEngine $evolutionEngine,
        protected \App\Modules\Simulation\Services\Core\RuleMutationService $mutationService,
        protected \App\Modules\Simulation\Core\Engines\Meta\InformationPropagationEngine $infoEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\PowerStructureEngine $powerEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\CulturalInfluenceEngine $cultureEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\MythogenesisEngine $mythEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\MeaningEngine $meaningEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\KnowledgeEvolutionEngine $knowledgeEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\ThermodynamicPhaseEngine $phaseEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\SingularityStabilityEngine $stabilityEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\AscensionEngine $ascensionEngine,
        protected \App\Modules\Simulation\Services\Ecology\ZenithMetricsService $metricsService,
        protected \App\Modules\Simulation\Core\Engines\Meta\CausalHistoryEngine $causalHistoryEngine,
        protected \App\Modules\Simulation\Core\Runtime\WorldKernel $kernel,
        protected \App\Modules\Narrative\Services\NarrativeEngine $narrativeEngine
    ) {}

    /**
     * @param  array<string, mixed>  $context  Optional context (e.g. engine response) passed to each stage
     */
    public function run(Universe $universe, int $tick, ?UniverseSnapshot $savedSnapshot = null, array $context = []): void
    {
        // Phase 37: Load Universal State
        $state = $this->stateManager->load($universe, $savedSnapshot);

        // Phase 80: High-Fidelity Orchestration (§World-Kernel Architecture)
        // Laravel backend acts as the Orchestrator for all 5 Phases (Environment -> Life -> Mind -> Social -> Meta)
        // All heavy lifting (Mass Actors, DSL, Physics) is dispatched to Rust/DSL via Systems in the Kernel.
        $this->kernel->execute($state, $tick);

        // Collect engine-level events (e.g. material_unlocked) for downstream listeners.
        $this->lastEngineEvents = $this->kernel->getLastEngineEvents();

        // Phase 72: Collect Zenith Meta-Metrics
        $metrics = $this->metricsService->getZenithReport($state);
        foreach ($metrics as $key => $values) {
            foreach ($values as $subKey => $val) {
                $state->set("meta.zenith.{$key}.{$subKey}", $val);
            }
        }

        // Phase 37: Save Universal State
        $this->stateManager->save($universe);

        // Sync back to snapshot if persisted, so the snapshot contains Kernel modifications
        if ($savedSnapshot) {
            $savedSnapshot->state_vector = $state->toArray();
            $savedSnapshot->save();
        }

        // Phase 80: Narrative Integration (Async — C2 fix)
        // Dispatch PulseNarrativeJob thay vì gọi pulse() đồng bộ.
        // Lý do: mỗi LLM call có thể mất 30s+, block toàn bộ tick loop trong batch mode.
        // Job chạy trên queue "narrative", không ảnh hưởng đến tốc độ simulation.
        $snapshotModel = $savedSnapshot ?? \App\Modules\Simulation\Models\UniverseSnapshot::where('universe_id', $universe->id)->where('tick', $tick)->first();

        if ($snapshotModel) {
            \App\Modules\Narrative\Jobs\PulseNarrativeJob::dispatch(
                $universe->id,
                $snapshotModel->id,
            );
        }
    }

    /**
     * Engine events collected from the last run() call.
     * Call immediately after run() before the next tick.
     *
     * @return array<int, mixed>
     */
    public function getLastEngineEvents(): array
    {
        return $this->lastEngineEvents;
    }
}



