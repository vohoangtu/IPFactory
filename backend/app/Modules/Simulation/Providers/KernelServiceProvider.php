<?php

namespace App\Modules\Simulation\Providers;

use App\Modules\Simulation\Engines\LegacyEngineAdapter;
use App\Modules\Simulation\Enums\EngineAuthority;
use App\Modules\Simulation\Enums\SimulationPhase;
use App\Modules\Simulation\Services\Kernel\PhaseRegistry;
use Illuminate\Support\ServiceProvider;

class KernelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // PhaseRegistry singleton — v2 engine registration system
        $this->app->singleton(PhaseRegistry::class, function ($app) {
            $registry = new PhaseRegistry();

            // ===================================================================
            // v2 PhaseRegistry Engine Registration
            // Wrap legacy SimulationEngine implementations via LegacyEngineAdapter
            // so they run through the new PhaseRegistry pipeline in WorldKernel.
            //
            // Authority classification (rust-authoritative-boundary):
            //   SUPPLEMENT = Rust doesn't compute this; always runs
            //   OVERLAP    = Rust already computes; skipped when rust_authoritative=true
            //   BRIDGE     = PHP wrapper delegating to Rust; skipped when rust_authoritative=true
            //   (STUB engines removed entirely — FinanceEngine, DiplomacyEngine, ProductionChainEngine)
            //   (RuleStage-duplicate engines removed — already called via RuleStage.run())
            // ===================================================================

            // --- Environment phase ---
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Physics\ClimateEngine::class),
                SimulationPhase::Environment,
                EngineAuthority::OVERLAP,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Physics\GeologicalEngine::class),
                SimulationPhase::Environment,
                EngineAuthority::OVERLAP,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Physics\MetabolicEngine::class),
                SimulationPhase::Environment,
                EngineAuthority::OVERLAP,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Physics\CosmicPressureEngine::class),
                SimulationPhase::Environment,
                EngineAuthority::OVERLAP,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Physics\RealityAnchorEngine::class),
                SimulationPhase::Environment,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Physics\MaterialEvolutionEngine::class),
                SimulationPhase::Environment,
            ));

            // --- Life phase ---
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Environment\LivingWorldEngine::class),
                SimulationPhase::Life,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Biological\AutopoieticEvolutionEngine::class),
                SimulationPhase::Life,
            ));

            // --- Mind phase ---
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Social\PsychologyEngine::class),
                SimulationPhase::Mind,
                EngineAuthority::OVERLAP,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Social\IdeaDiffusionEngine::class),
                SimulationPhase::Mind,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\NarrativeConflictEngine::class),
                SimulationPhase::Mind,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\InformationPropagationEngine::class),
                SimulationPhase::Mind,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\MeaningEngine::class),
                SimulationPhase::Mind,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\KnowledgeEvolutionEngine::class),
                SimulationPhase::Mind,
            ));

            // --- Social phase ---
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Social\GlobalEconomyEngine::class),
                SimulationPhase::Social,
                EngineAuthority::OVERLAP,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Social\PoliticsEngine::class),
                SimulationPhase::Social,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Social\CultureEngine::class),
                SimulationPhase::Social,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\PowerStructureEngine::class),
                SimulationPhase::Social,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Social\MarketEngine::class),
                SimulationPhase::Social,
                EngineAuthority::OVERLAP,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Social\TradeEngine::class),
                SimulationPhase::Social,
                EngineAuthority::OVERLAP,
            ));
            // STUB removed: DiplomacyEngine (empty — will be implemented in separate change)
            // STUB removed: FinanceEngine (empty — will be implemented in separate change)
            // STUB removed: ProductionChainEngine (empty — will be implemented in separate change)
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Social\InequalityEngine::class),
                SimulationPhase::Social,
                EngineAuthority::OVERLAP,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Social\LegitimacyEliteEngine::class),
                SimulationPhase::Social,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Social\CivilizationSettlementEngine::class),
                SimulationPhase::Social,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Social\CivilizationPhysicsEngine::class),
                SimulationPhase::Social,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Social\CivilizationLongCycleEngine::class),
                SimulationPhase::Social,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\CulturalInfluenceEngine::class),
                SimulationPhase::Social,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\ThermodynamicPhaseEngine::class),
                SimulationPhase::Social,
                EngineAuthority::BRIDGE,
            ));

            // --- Meta phase ---
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\MythogenesisEngine::class),
                SimulationPhase::Meta,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\CausalityEngine::class),
                SimulationPhase::Meta,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\IdeologyEngine::class),
                SimulationPhase::Meta,
            ));
            // AscensionEngine is a service class (not a SimulationEngine) — registered separately via KernelServiceProvider binding, not here.
            // RuleStage-duplicate removed: CausalHistoryEngine (called in RuleStage.run())
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\SingularityStabilityEngine::class),
                SimulationPhase::Meta,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\NarrativePropagationEngine::class),
                SimulationPhase::Meta,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\NarrativeInterpretationEngine::class),
                SimulationPhase::Meta,
            ));
            $registry->register(new LegacyEngineAdapter(
                $app->make(\App\Modules\Simulation\Core\Engines\Social\WarEngine::class),
                SimulationPhase::Meta,
                EngineAuthority::OVERLAP,
            ));

            return $registry;
        });

        // Phase 80: World Kernel & Primitive Systems
        $this->app->singleton(\App\Modules\Simulation\Core\Runtime\WorldKernel::class, function ($app) {
            $kernel = new \App\Modules\Simulation\Core\Runtime\WorldKernel(
                $app->make(\App\Modules\Simulation\Core\Runtime\State\StateManager::class),
                $app->make(PhaseRegistry::class),
            );

            // Phase 1: Environment
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_ENVIRONMENT,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_METABOLISM,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Physics\MetabolicEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_ENVIRONMENT,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_EXTRACTION,
                $app->make(\App\Modules\Simulation\Core\Runtime\Systems\ResourceSystem::class)
            );

            // Wave 1: Physics Foundation
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_ENVIRONMENT,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_METABOLISM,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Physics\RealityAnchorEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_ENVIRONMENT,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_ENTROPY,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Physics\CosmicPressureEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_ENVIRONMENT,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_EXTRACTION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Physics\GeologicalEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_ENVIRONMENT,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_METABOLISM,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Physics\ClimateEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_ENVIRONMENT,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_EXTRACTION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Physics\MaterialEvolutionEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_ENVIRONMENT,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_EXTRACTION,
                $app->make(\App\Modules\Simulation\Core\Runtime\Systems\MaterialWorldSystem::class)
            );

            // Phase 2: Life
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_LIFE,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_METABOLISM,
                $app->make(\App\Modules\Simulation\Core\Runtime\Systems\SurvivalSystem::class)
            );

            // Phase 3: Mind
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_MIND,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_DIFFUSION,
                $app->make(\App\Modules\Simulation\Core\Runtime\Systems\PropagationSystem::class)
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_MIND,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_OBSERVATION,
                $app->make(\App\Modules\Simulation\Core\Runtime\Systems\PsychologySystem::class)
            );

            // Phase 4: Social
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_COHESION,
                $app->make(\App\Modules\Simulation\Core\Runtime\Systems\PowerSystem::class)
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_COHESION,
                $app->make(\App\Modules\Simulation\Core\Runtime\Systems\AllianceSystem::class)
            );

            // Phase 5: Meta
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_META,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_CONFLICT,
                $app->make(\App\Modules\Simulation\Core\Runtime\Systems\ConflictSystem::class)
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_META,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_NARRATIVE,
                $app->make(\App\Modules\Simulation\Core\Runtime\Systems\MythCreationSystem::class)
            );

            // Phase 3: Mind (V10 Engines)
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_MIND,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_DIFFUSION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Meta\InformationPropagationEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_MIND,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_INNOVATION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Meta\MeaningEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_MIND,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_INNOVATION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Meta\KnowledgeEvolutionEngine::class))
            );

            // Phase 4: Social (V10 Engines)
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_COHESION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Meta\PowerStructureEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_ATTRACTION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Meta\CulturalInfluenceEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_CYCLE,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Meta\ThermodynamicPhaseEngine::class))
            );

            // Phase 5: Meta (V10 Engines)
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_META,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_NARRATIVE,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Meta\MythogenesisEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_META,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_NARRATIVE,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Meta\CulturalInfluenceEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_META,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_NARRATIVE,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Meta\IdeologyEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_META,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_CORRECTION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Meta\CausalHistoryEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_META,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_ENTROPY,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Meta\SingularityStabilityEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_META,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_ASCENSION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Meta\AscensionEngine::class))
            );

            // Phase 1: Environment (Stages)
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_ENVIRONMENT,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_METABOLISM,
                new \App\Modules\Simulation\Core\Runtime\Systems\StageSystemAdapter($app->make(\App\Modules\Simulation\Core\Runtime\Stages\RuleStage::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_ENVIRONMENT,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_METABOLISM,
                new \App\Modules\Simulation\Core\Runtime\Systems\StageSystemAdapter($app->make(\App\Modules\Simulation\Core\Runtime\Stages\EnvironmentStage::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_ENVIRONMENT,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_METABOLISM,
                new \App\Modules\Simulation\Core\Runtime\Systems\StageSystemAdapter($app->make(\App\Modules\Simulation\Core\Runtime\Stages\PhysicsStage::class))
            );

            // Phase 2: Life (Stages)
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_LIFE,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_PROPAGATION,
                new \App\Modules\Simulation\Core\Runtime\Systems\StageSystemAdapter($app->make(\App\Modules\Simulation\Core\Runtime\Stages\PopulationStage::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_LIFE,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_METABOLISM,
                new \App\Modules\Simulation\Core\Runtime\Systems\StageSystemAdapter($app->make(\App\Modules\Simulation\Core\Runtime\Stages\EcologyStage::class))
            );

            // Phase 3: Mind (FFI Vectorized Results + Behavioral Stages)
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_MIND,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_METABOLISM,
                new \App\Modules\Simulation\Core\Runtime\Systems\StageSystemAdapter($app->make(\App\Modules\Simulation\Core\Runtime\Stages\VectorizedActorStage::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_MIND,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_INNOVATION,
                new \App\Modules\Simulation\Core\Runtime\Systems\StageSystemAdapter($app->make(\App\Modules\Simulation\Core\Runtime\Stages\ActorStage::class))
            );

            // Phase 4: Social (Structural Stages)
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_COHESION,
                new \App\Modules\Simulation\Core\Runtime\Systems\StageSystemAdapter($app->make(\App\Modules\Simulation\Core\Runtime\Stages\CivilizationStage::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_ATTRACTION,
                new \App\Modules\Simulation\Core\Runtime\Systems\StageSystemAdapter($app->make(\App\Modules\Simulation\Core\Runtime\Stages\CivilizationFieldStage::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_EXTRACTION,
                new \App\Modules\Simulation\Core\Runtime\Systems\StageSystemAdapter($app->make(\App\Modules\Simulation\Core\Runtime\Stages\EconomyStage::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_EXTRACTION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Social\FinanceEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_EXTRACTION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Social\ProductionChainEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_COHESION,
                new \App\Modules\Simulation\Core\Runtime\Systems\StageSystemAdapter($app->make(\App\Modules\Simulation\Core\Runtime\Stages\PoliticsStage::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_COHESION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Social\DiplomacyEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_DIFFUSION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Social\CultureEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_DIFFUSION,
                new \App\Modules\Simulation\Core\Runtime\Systems\StageSystemAdapter($app->make(\App\Modules\Simulation\Core\Runtime\Stages\CultureStage::class))
            );

            // Phase 5: Meta (War & Cosmic)
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_META,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_CONFLICT,
                new \App\Modules\Simulation\Core\Runtime\Systems\StageSystemAdapter($app->make(\App\Modules\Simulation\Core\Runtime\Stages\WarStage::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_META,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_METABOLISM,
                new \App\Modules\Simulation\Core\Runtime\Systems\StageSystemAdapter($app->make(\App\Modules\Simulation\Core\Runtime\Stages\MetaCosmicStage::class))
            );


            // ===== Wave 2–6: NOTE =====
            // WARNING: Several engines below (MetabolicEngine, ClimateEngine, GeologicalEngine,
            // CosmicPressureEngine, MarketEngine, TradeEngine, GlobalEconomyEngine, InequalityEngine,
            // PoliticsEngine, PsychologyEngine, CulturalInfluenceEngine, ThermodynamicPhaseEngine,
            // WarEngine, PowerStructureEngine, LegitimacyEliteEngine, IdeaDiffusionEngine,
            // CivilizationSettlementEngine, CivilizationPhysicsEngine, CivilizationLongCycleEngine,
            // MythogenesisEngine, IdeologyEngine, AscensionEngine, SingularityStabilityEngine,
            // NarrativePropagationEngine, NarrativeInterpretationEngine, MeaningEngine, KnowledgeEvolutionEngine)
            // are ALSO registered in PhaseRegistry above (lines 33-196).
            // If WorldKernel and PhaseRegistry both run during a tick, these engines execute TWICE.
            // Verify that exactly ONE of WorldKernel.tick() or PhaseRegistry-based runner is called
            // per simulation tick. The OVERLAP/BRIDGE authority flags on PhaseRegistry entries
            // should suppress execution when rust_authoritative=true, but WorldKernel has no such guard.
            // ===== Wave 2: Living System =====
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_LIFE,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_METABOLISM,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Environment\LivingWorldEngine::class))
            );

            // ===== Wave 3: Economy =====
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_EXTRACTION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Social\MarketEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_EXTRACTION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Social\TradeEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_EXTRACTION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Social\GlobalEconomyEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_COHESION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Social\InequalityEngine::class))
            );

            // ===== Wave 4: Society & Politics =====
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_COHESION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Social\PoliticsEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_COHESION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Social\LegitimacyEliteEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_OBSERVATION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Social\PsychologyEngine::class))
            );

            // ===== Wave 5: Civilization =====
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_COHESION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Social\CivilizationSettlementEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_METABOLISM,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Social\CivilizationPhysicsEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_CYCLE,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Social\CivilizationLongCycleEngine::class))
            );
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_SOCIAL,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_DIFFUSION,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Social\IdeaDiffusionEngine::class))
            );

            // ===== Wave 6: Conflict =====
            $kernel->registerSystem(
                \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_META,
                \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_CONFLICT,
                new \App\Modules\Simulation\Core\Runtime\Systems\EngineSystemAdapter($app->make(\App\Modules\Simulation\Core\Engines\Social\WarEngine::class))
            );

            // ===== Wave 7: TODO — Activate Already-Implemented Engines =====
            // NOTE: The following engines exist with real logic but do NOT implement
            // SimulationEngine::handle(), run(), or update(). They need interface
            // refactoring before they can be registered via EngineSystemAdapter.
            // - HistoryEngine, HistoricalCycleEngine, HistoricalScarsEngine
            // - DynamicAttractorEngine, AttractorEngine, WorldWillEngine
            // - TransmigrationEngine, ChaosEngine, CapabilityEngine
            // - ActionExecutionEngine


            return $kernel;
        });

        $this->app->singleton(\App\Modules\Simulation\Core\Runtime\Systems\SurvivalSystem::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Runtime\Systems\ResourceSystem::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Runtime\Systems\PowerSystem::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Runtime\Systems\AllianceSystem::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Runtime\Systems\ConflictSystem::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Runtime\Systems\PropagationSystem::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Runtime\Systems\MythCreationSystem::class);
    }
}
