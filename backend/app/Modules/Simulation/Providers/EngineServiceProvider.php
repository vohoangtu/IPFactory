<?php

namespace App\Modules\Simulation\Providers;

use Illuminate\Support\ServiceProvider;

class EngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Modules\Simulation\Services\Meta\ConvergenceEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Culture\ResonanceEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\CausalCorrectionEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Ecology\PressureCalculator::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Cosmology\CosmicPhaseDetector::class);
        $this->app->singleton(\App\Modules\Simulation\Services\ScenarioEngine::class);
        // Cross-module contracts (P0-6): Narrative phụ thuộc interface → phá cycle Sim⇄Narr.
        $this->app->bind(\App\Contracts\ScenarioEngineInterface::class, \App\Modules\Simulation\Services\ScenarioEngine::class);
        $this->app->bind(\App\Contracts\ImplicitOrchestratorServiceInterface::class, \App\Modules\Simulation\Services\Core\ImplicitOrchestratorService::class);
        $this->app->bind(\App\Contracts\WorldAxiomActionInterface::class, \App\Modules\Simulation\Actions\WorldAxiomAction::class);
        $this->app->bind(\App\Contracts\Repositories\UniverseSnapshotRepositoryInterface::class, \App\Modules\Simulation\Repositories\UniverseSnapshotRepository::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Meta\MultiverseInteractionService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Meta\WorldRegulatorEngine::class);

        $this->app->singleton(\App\Modules\Simulation\Services\Society\AutonomicEvolutionEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Meta\MultiverseSchedulerEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Meta\TimelineSelectionEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Narrative\NarrativeExtractionEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Narrative\CivilizationMemoryEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Culture\MythologyGeneratorEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Culture\IdeologyEvolutionEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\GreatPersonEngine::class);
        $this->app->bind(
            \App\Contracts\UniverseSimilarityServiceInterface::class,
            \App\Modules\Simulation\Services\Core\StateVectorUniverseSimilarityService::class
        );
        $this->app->bind(\App\Contracts\CausalityGraphServiceInterface::class, function ($app) {
            return \config('worldos.causality.driver', 'null') === 'redis'
                ? $app->make(\App\Modules\Simulation\Services\Core\RedisCausalityGraphService::class)
                : $app->make(\App\Modules\Simulation\Services\Core\NullCausalityGraphService::class);
        });
        $this->app->bind(
            \App\Contracts\UniverseEvaluatorInterface::class,
            \App\Modules\Simulation\Services\Society\AutonomicEvolutionEngine::class
        );

        // State Management (Phase 37)
        $this->app->singleton(\App\Modules\Simulation\Core\Runtime\State\StateLoader::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Runtime\State\StateWriter::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Runtime\State\StateManager::class);

        // Simulation Kernel (effect-based, deterministic tick) + Event Bus (Tier 3, Phase 5 Track A)
        $this->app->singleton(\App\Modules\Simulation\Core\SimulationEventBus::class);
        $this->app->bind(\App\Modules\Simulation\Core\Contracts\WorldEventBusBackendInterface::class, function ($app) {
            $driver = \config('worldos.event_bus.driver', 'database');
            return $driver === 'redis_stream'
                ? new \App\Modules\Simulation\Core\EventBus\RedisStreamWorldEventBusBackend(true, \config('worldos.event_bus.stream_key'))
                : $app->make(\App\Modules\Simulation\Core\EventBus\DatabaseWorldEventBusBackend::class);
        });
        $this->app->singleton(\App\Modules\Simulation\Core\Contracts\WorldEventBusInterface::class, \App\Modules\Simulation\Core\WorldEventBus::class);
        $this->app->singleton(\App\Modules\Simulation\Core\WorldEventBus::class);
        $this->app->bind(\App\Contracts\SimulationEventStreamProducerInterface::class, function ($app) {
            if (! \config('worldos.event_stream.kafka_enabled', false)) {
                return $app->make(\App\Modules\Simulation\Services\EventStream\NullSimulationEventStreamProducer::class);
            }
            return new \App\Modules\Simulation\Services\EventStream\KafkaRestSimulationEventStreamProducer(
                \config('worldos.event_stream.rest_proxy_url'),
                \config('worldos.event_stream.topic_simulation_advanced'),
                \config('worldos.event_stream.topic_events'),
            );
        });
        $this->app->bind(\App\Modules\Simulation\Core\Contracts\WorldOsGraphServiceInterface::class, function ($app) {
            $enabled = \config('worldos.graph.enabled', false);
            $uri = \config('worldos.graph.uri', '');
            if (! $enabled || $uri === '') {
                return $app->make(\App\Modules\Simulation\Core\Graph\NullWorldOsGraphService::class);
            }
            return new \App\Modules\Simulation\Core\Graph\Neo4jWorldOsGraphService(
                $uri,
                \config('worldos.graph.username'),
                \config('worldos.graph.password')
            );
        });
        $this->app->singleton(\App\Modules\Simulation\Core\EffectResolver::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Support\SnapshotLoader::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Services\ZonePressureCalculator::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Services\TopologyResolver::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Services\CosmicSignalCollector::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Services\PhasePressureCalculator::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Physics\PotentialFieldEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Physics\StructuralDecayEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Physics\MetabolicEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\LawEvolutionEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\CausalityEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Social\AgricultureEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Social\PopulationEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Social\DiseaseEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Social\CivilizationFieldTheoryEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Biological\EcologicalCollapseEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Biological\EcologicalPhaseTransitionEngine::class);
        // AutopoieticEvolutionEngine — registered once (was duplicated in original)
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Biological\AutopoieticEvolutionEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Biological\CelestialAntibodyEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\MultiverseOsmosisEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\MetaAttractorEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\CausalHistoryEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\OmegaConvergenceEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\CausalBridgeEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\PostApotheosisEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\ObserverSpectrumService::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\ResonanceBleedingEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\DynamicLawEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\DeepTimeMemoryEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\HigherDimensionalEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\InfiniteRecursionEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Runtime\EventDrivenScheduler::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\IdealismEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\SingularityEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\InformationDensityEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\IdeologyEngine::class);
        // CulturalInfluenceEngine — registered once (was duplicated in original)
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\CulturalInfluenceEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\CausalCacheService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\RuleMutationService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\StructuralHashService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\HolographicCompressionService::class);

        // Batch 1: Physics & Metaphysics
        $this->app->singleton(\App\Modules\Simulation\Services\Cosmology\HeatDeathService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Cosmology\RealityCalibrationService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Cosmology\CosmicEnergyPoolService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Cosmology\SamsaraService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Society\SoulAnchorService::class);

        // Batch 2: Social & Civilization
        $this->app->singleton(\App\Modules\Simulation\Services\Politics\CivilizationDiscoveryService::class);
        // Cross-module contract (P0-6): entry point của Intelligence phụ thuộc interface → phá nốt cycle Sim⇄Intel.
        $this->app->bind(\App\Contracts\CivilizationDiscoveryServiceInterface::class, \App\Modules\Simulation\Services\Politics\CivilizationDiscoveryService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Society\DemographicRatesService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Society\SocialGraphService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Society\LegitimacyEliteService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Ecology\UrbanStressAgricultureService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Ecology\DemographicStages::class);

        // Batch 3: Narrative & Cognition
        $this->app->singleton(\App\Modules\Simulation\Services\Society\ActorCognitiveService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Narrative\CivilizationNarrativeInterpreter::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Narrative\GrandNarrativeService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\HeroLifecycleService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Culture\IdeologyConversionService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\KnowledgeGraphService::class);

        // Batch 4: Technical & Infrastructure
        $this->app->singleton(\App\Modules\Simulation\Services\Cosmology\SimulationClock::class);
        $this->app->singleton(\App\Support\Simulation\SimulationPRNG::class);

        // Batch 5: Intelligence & Emergence
        $this->app->singleton(\App\Modules\Simulation\Services\Ecology\AnomalyGeneratorService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Ecology\EvolutionarySparkService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\SelfImprovingSimulationService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Society\TheDreamingService::class);
        // ZenithMetricsService — registered once (was duplicated in original)
        $this->app->singleton(\App\Modules\Simulation\Services\Ecology\ZenithMetricsService::class);
        // ReasoningService — registered once (was duplicated in original)
        $this->app->singleton(\App\Modules\Simulation\Services\Core\ReasoningService::class);

        // Batch 6: Rule & DSL Engines
        $this->app->singleton(\App\Modules\Simulation\Core\Runtime\RuleVM\EffectExecutor::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService::class);

        $this->app->singleton(\App\Modules\Simulation\Services\RuleEngine\FfiRuleEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Runtime\RuleVM\RuleGraphService::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Runtime\RuleVM\DeployRuleProposalService::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Runtime\RuleVM\EventTriggerProcessor::class);

        // Batch 7: Infrastructure & Support
        $this->app->singleton(\App\Modules\Simulation\Services\Core\SimulationMetricsExporter::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\SimulationMetricsLogger::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\SimulationTracer::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\MetricsExtractor::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Meta\WorldSimulationStatusService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\StateVectorUniverseSimilarityService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\NullUniverseSimilarityService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Meta\UniverseRuntimeService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\CheatGranterService::class);

        // Batch 8: Multiverse-Level Logic
        $this->app->singleton(\App\Modules\Simulation\Services\Meta\MultiverseSovereigntyService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Meta\MultiverseSynthesisService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Cosmology\CosmogenesisService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Meta\TemporalSyncService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Meta\ParadoxResolver::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Culture\ResonanceAuditorService::class);

        // Batch 9: Emergent Physics & Social
        $this->app->singleton(\App\Modules\Simulation\Services\Core\FieldDiffusionEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Ecology\GeographyResourceService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\GreatPersonLegacyService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\InnovationRateService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Society\InstitutionDecayService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Ecology\KernelMutationService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\MacroAgentSpawnService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\MetaEdictService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Ecology\SurvivalPruningService::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Cosmology\VaultService::class);

        // Batch 10: FFI & Clients
        $this->app->singleton(\App\Modules\Simulation\Services\Cosmology\AxiomRegistry::class);

        // Vocation V1 Engine Services
        $this->app->singleton(\App\Modules\Simulation\Vocation\DSL\ExpressionEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Vocation\Services\ElementInteractionService::class);
        $this->app->singleton(\App\Modules\Simulation\Vocation\Services\VocationEvolutionService::class);
        $this->app->singleton(\App\Modules\Simulation\Vocation\Services\VocationEngine::class);

        $this->app->singleton(\App\Modules\Simulation\Services\Core\HttpSimulationEngineClient::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\StubSimulationEngineClient::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\FfiActorEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Services\FeatureExtractionService::class);

        // Phase 100: Power System Transition (Decoupling Era from Power System)
        $this->app->singleton(\App\Modules\Simulation\Services\Transition\Transformers\EntropyTransformer::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Transition\Transformers\StabilityBinder::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Transition\Transformers\CausalityRewriter::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Transition\Transformers\RealityStrainSimulator::class);

        $this->app->singleton(\App\Modules\Simulation\Services\Transition\Guards\EnergyInvariantGuard::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Transition\Guards\CausalityInvariantGuard::class);

        $this->app->singleton(\App\Modules\Simulation\Services\Transition\TransitionProcessor::class, function ($app) {
            $transformers = [
                $app->make(\App\Modules\Simulation\Services\Transition\Transformers\EntropyTransformer::class),
                $app->make(\App\Modules\Simulation\Services\Transition\Transformers\StabilityBinder::class),
                $app->make(\App\Modules\Simulation\Services\Transition\Transformers\CausalityRewriter::class),
                $app->make(\App\Modules\Simulation\Services\Transition\Transformers\RealityStrainSimulator::class),
            ];

            $guards = [
                $app->make(\App\Modules\Simulation\Services\Transition\Guards\EnergyInvariantGuard::class),
                $app->make(\App\Modules\Simulation\Services\Transition\Guards\CausalityInvariantGuard::class),
            ];

            return new \App\Modules\Simulation\Services\Transition\TransitionProcessor($transformers, $guards);
        });

        // Advanced V10 Engines
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\InformationPropagationEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\PowerStructureEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\MythogenesisEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\MeaningEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\KnowledgeEvolutionEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\ThermodynamicPhaseEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\SingularityStabilityEngine::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Engines\Meta\AscensionEngine::class, function ($app) {
            return new \App\Modules\Simulation\Core\Engines\Meta\AscensionEngine(
                $app->make(\App\Modules\Simulation\Services\Meta\WorldTemplateManager::class),
                $app->make(\App\Contracts\LlmNarrativeClientInterface::class),
            );
        });

        // AdvanceSimulationAction (Legacy facade, keep until fully replaced by WorldKernel)
        $this->app->singleton(\App\Modules\Intelligence\Services\CivilizationCollapseEngine::class);
        $this->app->tag(\config('worldos.engine_registry.engines', []), 'simulation_engine');
        $this->app->singleton(\App\Modules\Simulation\Core\EngineRegistry::class, function ($app) {
            $registry = new \App\Modules\Simulation\Core\EngineRegistry();
            foreach ($app->tagged('simulation_engine') as $engine) {
                $registry->register($engine);
            }
            return $registry;
        });
        $this->app->singleton(\App\Modules\Simulation\Core\SimulationScheduler::class, function ($app) {
            return new \App\Modules\Simulation\Core\SimulationScheduler(
                $app->make(\App\Modules\Simulation\Core\EngineRegistry::class),
                $app->make(\App\Modules\Simulation\Core\Runtime\Contracts\TickSchedulerInterface::class)
            );
        });
        $this->app->singleton(\App\Modules\Simulation\Core\Services\SimulationReplayService::class, function ($app) {
            return new \App\Modules\Simulation\Core\Services\SimulationReplayService(
                $app->make(\App\Modules\Simulation\Core\Runtime\WorldKernel::class),
            );
        });

        // Cross-module interface bindings
        $this->app->bind(\App\Contracts\RuleVmInterface::class, \App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService::class);
        $this->app->bind(\App\Contracts\DecisionEngineInterface::class, \App\Modules\Simulation\Core\Engines\Meta\DecisionEngine::class);
    }
}
