<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Core\Runtime;

use App\Modules\Simulation\Core\Domain\EngineExecutionRecord;
use App\Modules\Simulation\Core\Domain\EngineResult;
use App\Modules\Simulation\Core\Domain\PhaseExecutionResult;
use App\Modules\Simulation\Core\Domain\SimulationTickResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\Causality\ImpactReport;
use App\Modules\Simulation\Core\Runtime\Kernel\AgentBatchProcessor;
use App\Modules\Simulation\Core\Runtime\Kernel\PhaseExecutor;
use App\Modules\Simulation\Core\Runtime\Kernel\TickFinalizer;
use App\Modules\Simulation\Core\Runtime\State\StateManager;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Enums\SimulationPhase;
use App\Modules\Simulation\Services\Kernel\PhaseRegistry;
use Illuminate\Support\Facades\Log;

/**
 * WorldKernel – The core of WorldOS simulation (§World-Kernel).
 *
 * "Laravel backend chỉ đóng vai orchestrator"
 * Orchestrates all 15 Primitive Rules across 5 Reality Phases.
 *
 * Supports two registration modes during migration:
 * 1. Legacy: registerSystem() into orchestrationMap (backward compatible)
 * 2. New: PhaseRegistry with AbstractWorldOSEngine instances
 *
 * Delegates to:
 * - AgentBatchProcessor: Agent actions & gRPC processing
 * - PhaseExecutor: 5-phase reality execution (legacy systems)
 * - TickFinalizer: Causal impacts, domain events, finalization
 */
class WorldKernel
{
    // --- 5 PHASES OF REALITY ---
    public const PHASE_ENVIRONMENT = 'environment';
    public const PHASE_LIFE        = 'life';
    public const PHASE_MIND        = 'mind';
    public const PHASE_SOCIAL      = 'social';
    public const PHASE_META        = 'meta';

    // --- 15 PRIMITIVE RULE CATEGORIES ---
    public const RULE_METABOLISM   = 'metabolism';
    public const RULE_EXTRACTION   = 'extraction';
    public const RULE_INNOVATION   = 'innovation';
    public const RULE_DIFFUSION    = 'diffusion';
    public const RULE_COHESION     = 'cohesion';
    public const RULE_ENTROPY      = 'entropy';
    public const RULE_CONFLICT     = 'conflict';
    public const RULE_PROPAGATION  = 'propagation';
    public const RULE_RECURSION    = 'recursion';
    public const RULE_ASCENSION    = 'ascension';
    public const RULE_CORRECTION   = 'correction';
    public const RULE_OBSERVATION  = 'observation';
    public const RULE_BRIDGE       = 'bridge';
    public const RULE_NARRATIVE    = 'narrative';
    public const RULE_CYCLE        = 'cycle';
    public const RULE_ATTRACTION   = 'attraction';

    /** @var array<string, array<string, object[]>> Legacy orchestration map */
    protected array $orchestrationMap = [];

    /** @var ImpactReport[] */
    protected array $tickImpacts = [];

    /** @var PhaseExecutionResult[] Results from the latest tick's v2 engines. */
    protected array $phaseResults = [];

    protected AgentBatchProcessor $agentProcessor;
    protected PhaseExecutor $phaseExecutor;
    protected TickFinalizer $tickFinalizer;

    public function __construct(
        protected StateManager $stateManager,
        protected ?PhaseRegistry $registry = null,
    ) {
        $this->initOrchestrationMap();
        $this->agentProcessor = new AgentBatchProcessor();
        $this->phaseExecutor = new PhaseExecutor();
        $this->tickFinalizer = new TickFinalizer();
    }

    protected function initOrchestrationMap(): void
    {
        foreach ([self::PHASE_ENVIRONMENT, self::PHASE_LIFE, self::PHASE_MIND, self::PHASE_SOCIAL, self::PHASE_META] as $phase) {
            $this->orchestrationMap[$phase] = [];
        }
    }

    /**
     * Legacy system registration — kept for backward compatibility.
     *
     * @deprecated Register engines via PhaseRegistry instead.
     */
    public function registerSystem(string $phase, string $category, object $system): void
    {
        $this->orchestrationMap[$phase][$category][] = $system;
    }

    /**
     * Run the full simulation orchestration for a single tick.
     */
    public function execute(WorldState $state, int $tick): void
    {
        $startTime = microtime(true);
        Log::debug("WorldKernel: Starting Orchestration Tick $tick");
        $this->tickImpacts = [];
        $this->phaseResults = [];

        // 1. PHASE 0: Agents Act First (§V8 Realignment)
        // V9: Ensure zones are prepopulated with agents from the single source of truth
        $state->syncAgentsToZones();

        // Before world environment updates, agents must decide and act.
        $this->agentProcessor->executeAgentActions($state, $tick);

        // V9: Resync after actions (to reflect movement/changes before systems run)
        $state->syncAgentsToZones();

        // 2. PHASE 1-5: Sequential Reality Phases
        $rustAuthoritative = (bool) config('worldos_simulation.simulation.rust_authoritative', true);
        foreach (SimulationPhase::inOrder() as $phase) {
            $phaseStart = microtime(true);

            // 2a. Legacy orchestration map systems (via registerSystem — @deprecated).
            // GUARD: skip if PhaseRegistry has engines for this phase to prevent double execution.
            // Engines registered in KernelServiceProvider Wave 1–6 overlap with PhaseRegistry entries.
            $phaseKey = $phase->key();
            $categories = $this->orchestrationMap[$phaseKey] ?? [];
            // Use rustAuthoritative=false to check raw registration (ignores authority filtering).
            // This prevents legacy path from running when the same phase has v2 engines registered,
            // regardless of whether those engines are currently active under rust_authoritative mode.
            $registryHasEngines = $this->registry !== null
                && !empty($this->registry->getEnginesForPhase($phase, [], false));
            if (!empty($categories) && !$registryHasEngines) {
                $this->phaseExecutor->executePhase($phaseKey, $categories, $state, $tick, $this->tickImpacts);
            }

            // 2b. v2 PhaseRegistry engines
            if ($this->registry !== null) {
                $phaseResult = $this->executeRegistryPhase($phase, $state, $tick);
                $this->phaseResults[] = $phaseResult;
            }

            $phaseMs = round((microtime(true) - $phaseStart) * 1000, 2);
            Log::debug("WorldKernel: Phase [{$phaseKey}] completed in {$phaseMs}ms");
        }

        // 3. Process Global Emergence: State Transition Engine (ISTE)
        $iste = app(\App\Modules\Simulation\Core\Runtime\Engines\StateTransitionEngine::class);
        $iste->run($state, $this->tickImpacts, $tick);

        // 4. Narrative-Driven: Cleanup & Feedback
        $this->tickFinalizer->processCausalImpacts($state, $tick, $this->tickImpacts);
        $this->tickFinalizer->finalizeTick($state, $tick);

        $totalMs = round((microtime(true) - $startTime) * 1000, 2);
        Log::debug("WorldKernel: Orchestration Tick $tick Completed in {$totalMs}ms");
    }

    /**
     * Execute v2 engines registered via PhaseRegistry for a given phase.
     * Respects rust_authoritative config — OVERLAP/BRIDGE engines are skipped when Rust is authoritative.
     */
    protected function executeRegistryPhase(SimulationPhase $phase, WorldState $state, int $tick): PhaseExecutionResult
    {
        $result = new PhaseExecutionResult($phase);

        $rustAuthoritative = (bool) config('worldos_simulation.simulation.rust_authoritative', true);
        $engines = $this->registry->getEnginesForPhase($phase, [], $rustAuthoritative);

        if (empty($engines)) {
            return $result;
        }

        $ctx = new TickContext(
            universeId: 0, // Will be set from state when available
            tick: $tick,
            seed: crc32((string) $tick),
        );

        foreach ($engines as $engine) {
            $engineStart = microtime(true);

            try {
                $engineResult = $engine->execute($state, $ctx);
                $engineResult->metrics['duration_ms'] = round((microtime(true) - $engineStart) * 1000, 2);

                Log::debug("WorldKernel: v2 Engine [{$engine->name()}] in phase [{$phase->key()}]", [
                    'skipped' => $engineResult->skipped,
                    'mutations' => count($engineResult->stateChanges),
                    'events' => count($engineResult->events),
                    'duration_ms' => $engineResult->getDurationMs(),
                ]);
            } catch (\Throwable $e) {
                Log::error("WorldKernel: v2 Engine [{$engine->name()}] failed", [
                    'phase' => $phase->key(),
                    'error' => $e->getMessage(),
                ]);

                $engineResult = EngineResult::skipped("Engine error: {$e->getMessage()}");
                $engineResult->metrics['duration_ms'] = round((microtime(true) - $engineStart) * 1000, 2);
            }

            $result->addEngineResult($engine->name(), $engineResult);
        }

        return $result;
    }

    /**
     * Get results from the latest tick's v2 engine execution.
     *
     * @return PhaseExecutionResult[]
     */
    public function getPhaseResults(): array
    {
        return $this->phaseResults;
    }

    /**
     * Return all engine events emitted during the last execute() call.
     * Safe to call after execute(); resets on the next execute() call.
     *
     * @return array<int, mixed>
     */
    public function getLastEngineEvents(): array
    {
        $events = [];
        foreach ($this->phaseResults as $phaseResult) {
            foreach ($phaseResult->getEngineResults() as $engineResult) {
                foreach ($engineResult->events as $ev) {
                    $events[] = $ev;
                }
            }
        }
        return $events;
    }

    /**
     * Run a single tick and return a SimulationTickResult.
     *
     * Bridge method that provides API parity with the deprecated SimulationKernel::runTick(),
     * enabling SimulationReplayService and other consumers to migrate away from SimulationKernel.
     */
    public function runTick(WorldState $state, TickContext $ctx): SimulationTickResult
    {
        $this->execute($state, $ctx->getTick());

        // Collect events and metrics from v2 PhaseRegistry engine results
        $allEvents = [];
        $allCausalLinks = [];
        $engineMetrics = [];

        foreach ($this->phaseResults as $phaseResult) {
            foreach ($phaseResult->getEngineResults() as $engineName => $engineResult) {
                foreach ($engineResult->events as $ev) {
                    $allEvents[] = $ev;
                }
                foreach ($engineResult->causalLinks as $type => $pid) {
                    $allCausalLinks[$type] = $pid;
                }
                $engineMetrics[] = new EngineExecutionRecord(
                    engineName: $engineName,
                    elapsedMs: $engineResult->getDurationMs(),
                    effectsCount: count($engineResult->stateChanges),
                    eventsCount: count($engineResult->events),
                    priority: 'NORMAL',
                    wasSkipped: $engineResult->skipped,
                );
            }
        }

        return new SimulationTickResult(
            state: $state,
            events: $allEvents,
            causalLinks: $allCausalLinks,
            engineMetrics: $engineMetrics,
        );
    }
}
