<?php

namespace App\Modules\Simulation\Listeners;

use App\Modules\World\Models\Universe;

use App\Modules\Intelligence\Actions\DecideUniverseAction;
use App\Modules\Narrative\Actions\ApplyMythScarAction;
use App\Modules\Simulation\Actions\RunMicroModeAction;
use App\Modules\Simulation\Contracts\UniverseRepositoryInterface;
use App\Modules\Simulation\Core\Engines\Meta\AttractorEngine;
use App\Modules\Simulation\Core\Engines\Meta\DynamicAttractorEngine;
use App\Modules\Simulation\Core\Runtime\Domain\UniverseState;
use App\Modules\Simulation\Core\Runtime\RuleVM\EventTriggerProcessor;
use App\Modules\Simulation\Core\Support\SimulationRandom;
use App\Modules\Simulation\Events\UniverseSimulationPulsed;
use App\Modules\Simulation\Repositories\UniverseRepository;
use App\Modules\Simulation\Services\Core\ObservationInterferenceEngine;
use App\Modules\Simulation\Services\Cosmology\CosmicEnergyPoolService;
use App\Modules\Simulation\Services\Cosmology\EpochEngine;
use App\Modules\Simulation\Services\Evaluation\ActorDecisionOrchestrator;
use App\Modules\Simulation\Services\Evaluation\MetricsAggregationService;
use App\Modules\Simulation\Services\Evaluation\NarrativeChronicleService;
use App\Modules\Simulation\Services\Evaluation\StrategicActionHandler;
use App\Modules\Simulation\Services\Meta\TrajectoryModelingEngine;
use App\Modules\Simulation\Services\Meta\VoidExplorationEngine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class EvaluateSimulationResult
{
    public function __construct(
        protected DecideUniverseAction $decideUniverseAction,
        protected ApplyMythScarAction $applyMythScarAction,
        protected RunMicroModeAction $runMicroModeAction,
        protected UniverseRepository $universeRepository,
        protected UniverseRepositoryInterface $simulationUniverseRepository,
        protected \App\Modules\Institutions\Services\GreatFilterEngine $greatFilterEngine,
        protected \App\Modules\Institutions\Services\AscensionEngine $ascensionEngine,
        protected \App\Modules\Simulation\Services\Meta\ConvergenceEngine $convergenceEngine,
        protected \App\Modules\Institutions\Services\WorldEdictEngine $worldEdictEngine,
        protected \App\Modules\Institutions\Services\OmegaPointEngine $omegaPointEngine,
        protected \App\Modules\Institutions\Services\ZoneConflictEngine $zoneConflictEngine,
        protected VoidExplorationEngine $voidExplorationEngine,
        protected EpochEngine $epochEngine,
        protected \App\Modules\Simulation\Services\Core\CausalCorrectionEngine $causalCorrectionEngine,
        protected \App\Modules\Simulation\Services\Culture\ResonanceEngine $resonanceEngine,
        protected ObservationInterferenceEngine $observationInterferenceEngine,
        protected TrajectoryModelingEngine $trajectoryModelingEngine,
        protected \App\Modules\Simulation\Services\Meta\MultiverseInteractionService $multiverseInteractionService,
        protected \App\Modules\Simulation\Services\Meta\WorldRegulatorEngine $worldRegulatorEngine,
        protected AttractorEngine $attractorEngine,
        protected DynamicAttractorEngine $dynamicAttractorEngine,
        protected EventTriggerProcessor $eventTriggerProcessor,
        protected \App\Modules\Simulation\Services\Culture\IdeologyEvolutionEngine $ideologyEvolutionEngine,
        protected \App\Modules\Simulation\Services\Core\GreatPersonEngine $greatPersonEngine,
        protected \App\Modules\Simulation\Services\Core\GreatPersonLegacyService $greatPersonLegacyService,
        protected \App\Modules\Simulation\Services\Core\MacroAgentSpawnService $macroAgentSpawnService,
        protected \App\Modules\Simulation\Core\Engines\Social\IdeaDiffusionEngine $ideaDiffusionEngine,
        protected \App\Modules\Simulation\Services\Society\InstitutionDecayService $institutionDecayService,
        protected \App\Modules\Simulation\Core\Contracts\WorldEventBusInterface $worldEventBus,
        protected CosmicEnergyPoolService $cosmicEnergyPoolService,
        protected \App\Modules\Simulation\Services\Core\HeroLifecycleService $heroLifecycleService,
        protected \App\Modules\Simulation\Services\Culture\GenreEvolutionService $genreEvolutionService,
        protected MetricsAggregationService $metricsAggregation,
        protected StrategicActionHandler $strategicAction,
        protected ActorDecisionOrchestrator $actorDecisionOrchestrator,
        protected NarrativeChronicleService $narrativeChronicleService,
    ) {
    }

    public function handle(UniverseSimulationPulsed $event): void
    {
        $universe = $event->universe;
        $snapshot = $event->snapshot;

        try {
            // Strategic Decision
            $decisionData = $this->decideUniverseAction->execute($snapshot);
            $action = $decisionData['action'] ?? 'continue';

            // 3. Apply Myth Scars
            $this->applyMythScarAction->execute($universe, $snapshot, $decisionData);

            // 4. Run Micro Mode
            $this->runMicroModeAction->execute($universe, $snapshot, $decisionData);

            // Seeded RNG for deterministic simulation (replayable)
            $rng = new SimulationRandom((int) ($universe->seed ?? 0), (int) $snapshot->tick, 0);

            // Emerging Civilizations (Handled by Institutions Module)
            if ($this->narrativeChronicleService->shouldRun('zone_conflict', $universe, $snapshot)) {
                $this->zoneConflictEngine->resolveConflicts($universe, $snapshot, $rng);
            }

            // Attractor field: evaluate active attractors, persist to state_vector for event modulation
            $this->attractorEngine->evaluate($universe, $snapshot);
            $universe->refresh();

            // Dynamic attractors: decay instances, spawn from rules, merge into active_attractors
            $this->dynamicAttractorEngine->process($universe, $snapshot, $rng);
            $universe->refresh();

            // Event trigger processing (data-driven: rules, cooldown, probability → BranchEvent)
            $this->eventTriggerProcessor->process($universe, $snapshot, $rng);

            // Simulation Module Processing (DDD)
            $universeEntity = $this->simulationUniverseRepository->findById($universe->id);
            if ($universeEntity) {
                $this->voidExplorationEngine->process($universeEntity, (int)$snapshot->tick);
                $this->epochEngine->process($universeEntity, $snapshot);

                $isBeingObserved = $universe->last_observed_at &&
                                   $universe->last_observed_at->diffInSeconds(Carbon::now()) < 30;
                $this->observationInterferenceEngine->process($universeEntity, (int)$snapshot->tick, $isBeingObserved);
                $this->trajectoryModelingEngine->process($universeEntity, (int)$snapshot->tick);
            }

            $this->convergenceEngine->process($universe, (int)$snapshot->tick);
            $this->causalCorrectionEngine->process($universe, $snapshot);
            $this->resonanceEngine->process($universe, $snapshot);

            // 6. Strategic Actions (Fork/Archive/Mutate/Merge/Promote) — AEE decisions (doc §13)
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

            // 6. Calculate & Store Pressure Metrics
            $this->storePressureMetrics($universe, $snapshot);

            // Power Economy: cosmic energy pool (after metrics final)
            $this->cosmicEnergyPoolService->processPulse($universe, $snapshot);
            $universe->refresh();

            // 6a. WorldEvent + Historical Fact (Phase 1–2): build event → record fact → publish event.
            if (config('worldos.narrative_v2.enable_world_event', true)) {
                try {
                    $this->narrativeChronicleService->processWorldEvents(
                        $universe,
                        $snapshot,
                        $decisionData,
                        $event->engineResponse['scars'] ?? []
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('EventNormalizer/HistoricalFact failed: ' . $e->getMessage());
                }
            }

            // 6b. Deep Sim Phase B: spawn macro agents (ruler/army) when conditions met; persist to state_vector
            $universe->refresh();
            try {
                $this->macroAgentSpawnService->spawnIfEligible($universe, $snapshot);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('MacroAgentSpawnService spawn failed: ' . $e->getMessage());
            }

            // 6c. Actor Decision (Phase 2): key actors → capabilities → action_distribution → roll → actor_events
            if (config('worldos.pulse.run_actor_decision', false)) {
                if ($this->narrativeChronicleService->shouldRun('actor_decision', $universe, $snapshot)) {
                    try {
                        $this->runActorDecisionForUniverse($universe, $snapshot, $rng);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Actor decision failed: ' . $e->getMessage());
                    }
                }
            }
            if (config('worldos.idea_diffusion.run_on_pulse', false)) {
                if ($this->narrativeChronicleService->shouldRun('idea_diffusion', $universe, $snapshot)) {
                    try {
                        $this->ideaDiffusionEngine->process($universe, (int) $snapshot->tick);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Idea diffusion failed: ' . $e->getMessage());
                    }
                }
            }
            if (config('worldos.institution.run_decay_on_pulse', false)) {
                if ($this->narrativeChronicleService->shouldRun('institution_decay', $universe, $snapshot)) {
                    try {
                        $this->institutionDecayService->process($universe, (int) $snapshot->tick);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Institution decay failed: ' . $e->getMessage());
                    }
                }
            }

            // 7. Detect & Dispatch Anomalies
            $this->detectAnomalies($universe, $snapshot);

            // 8. World Edicts (Governance)
            $this->worldEdictEngine->decree($universe, $snapshot);

            // 9. Great Filter, Ascension, Supreme Entities & Convergence (Handled by Institutions Module)
            $this->greatFilterEngine->process($universe, (int)$snapshot->tick, $snapshot->state_vector ?? [], $rng);
            $this->convergenceEngine->process($universe, (int)$snapshot->tick);

            $uState = UniverseState::fromModels($universe, $snapshot);
            $this->ascensionEngine->evaluate($uState);

            $this->omegaPointEngine->process($universe, $snapshot);

            // 9b. Ideology Evolution & Great Person (Phase K)
            if (config('worldos.pulse.run_ideology', true)) {
                if ($this->narrativeChronicleService->shouldRun('ideology_evolution', $universe, $snapshot)) {
                    try {
                        $ideologyResult = $this->ideologyEvolutionEngine->getDominantIdeology($universe);
                        if (! empty($ideologyResult['previous_dominant'])) {
                            $this->ideologyEvolutionEngine->recordShiftIfSignificant(
                                $universe,
                                (int) $snapshot->tick,
                                $ideologyResult['dominant'],
                                $ideologyResult['previous_dominant']
                            );
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Pulse: Ideology evolution failed: ' . $e->getMessage());
                    }
                }
            }
            if (config('worldos.pulse.run_great_person', true)) {
                if ($this->narrativeChronicleService->shouldRun('great_person', $universe, $snapshot)) {
                    try {
                        $this->greatPersonEngine->spawnIfEligible($universe, (int) $snapshot->tick);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Pulse: Great Person spawn failed: ' . $e->getMessage());
                    }
                }
            }
            if (config('worldos.pulse.run_great_person_legacy', true)) {
                try {
                    $this->greatPersonLegacyService->writeToStateVector($universe, (int) $snapshot->tick);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Pulse: Great Person legacy aggregate failed: ' . $e->getMessage());
                }
            }

            // Phase 7: Hero Lifecycle (latent -> myth transition)
            try {
                $this->heroLifecycleService->process($universe, (int) $snapshot->tick);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Pulse: Hero lifecycle process failed: ' . $e->getMessage());
            }

            // 10. AI Narrative (Epistemic Instability)
            $this->createNarrativeChronicle($universe, $snapshot);

            // 11. Multiverse Interaction
            $this->multiverseInteractionService->detectResonance($universe);

            // 12. World Autonomic Regulation
            if ($universe->world) {
                $this->worldRegulatorEngine->process($universe->world);
                $this->genreEvolutionService->evaluateEvolution($universe);
            }

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Simulation evaluation failed in listener: " . $e->getMessage());
        }
    }

    protected function handleMerge($universe, array $decision): void
    {
        $this->strategicAction->handleMerge($universe, $decision);
    }

    protected function handlePromote($universe, array $decision): void
    {
        $this->strategicAction->handlePromote($universe, $decision);
    }

    protected function handleFork($universe, int $tick, array $decision): void
    {
        $this->strategicAction->handleFork($universe, $tick, $decision);
    }

    protected function applySelectivePressure($universe, $snapshot, array $decisionData): void
    {
        $this->strategicAction->applySelectivePressure($universe, $snapshot, $decisionData);
    }

    protected function storePressureMetrics($universe, $snapshot): void
    {
        $this->metricsAggregation->storePressureMetrics($universe, $snapshot);
    }

    /**
     * Enforce metrics invariant [0,1] when writing. Clamp known scalar keys and ethos dimensions.
     */
    protected function clampMetricsToUnitInterval(array $metrics): array
    {
        return $this->metricsAggregation->clampMetricsToUnitInterval($metrics);
    }

    protected function detectAnomalies($universe, $snapshot): void
    {
        $entropy = (float) $snapshot->entropy;
        $stability = (float) $snapshot->stability_index;

        if ($entropy > 0.95) {
            event(new \App\Modules\Simulation\Events\AnomalyDetected($universe, [
                'title' => 'Cánh cửa Hư vô (Void Gate) Mở ra',
                'description' => 'Entropy đạt mức tới hạn ('.round($entropy * 100, 2).'%). Cấu trúc thực tại đang tan biến.',
                'severity' => 'CRITICAL'
            ]));
        } elseif ($stability < 0.2) {
            event(new \App\Modules\Simulation\Events\AnomalyDetected($universe, [
               'title' => 'Sụp đổ Cấu trúc Xã hội',
               'description' => 'Chỉ số ổn định thấp kỷ lục ('.round($stability, 4).'). Các định chế đang tan rã.',
               'severity' => 'CRITICAL'
            ]));
        } elseif (($snapshot->metrics['material_stress'] ?? 0) > 0.8) {
            event(new \App\Modules\Simulation\Events\AnomalyDetected($universe, [
               'title' => 'Căng thẳng Vật chất Cực độ',
               'description' => 'Áp lực lên hạ tầng vượt ngưỡng an toàn. Nguy cơ ly khai diện rộng.',
               'severity' => 'WARN'
            ]));
        }
    }

    protected function createNarrativeChronicle($universe, $snapshot): void
    {
        $this->narrativeChronicleService->createNarrativeChronicle($universe, $snapshot);
    }

    /**
     * Run narrative interval jobs: era (every era_interval), religion spread, causal_trajectory, legend.
     */
    protected function runNarrativeIntervals(\App\Modules\World\Models\Universe $universe, $snapshot): void
    {
        $this->narrativeChronicleService->runNarrativeIntervals($universe, $snapshot);
    }

    /**
     * Build belief context for ActorDecisionEngine: religion, causal_trajectory belief, legend level.
     */
    protected function getBeliefContextForActor(\App\Modules\Intelligence\Models\Actor $actor): array
    {
        return $this->actorDecisionOrchestrator->getBeliefContextForActor($actor);
    }

    /**
     * Phase 2: Run CapabilityEngine + ActorDecisionEngine for key actors; record action in actor_events.
     */
    protected function runActorDecisionForUniverse($universe, $snapshot, SimulationRandom $rng): void
    {
        $this->actorDecisionOrchestrator->runActorDecisionForUniverse($universe, $snapshot, $rng);
    }
}
