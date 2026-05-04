<?php

namespace App\Modules\Simulation\Providers;

use Illuminate\Support\ServiceProvider;

class PipelineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // SpawnPipeline
        $this->app->singleton(\App\Modules\Simulation\Core\Domain\Pipelines\SpawnPipeline::class, function ($app) {
            return new \App\Modules\Simulation\Core\Domain\Pipelines\SpawnPipeline([
                $app->make(\App\Modules\Simulation\Core\Domain\Pipelines\Steps\InheritStateStep::class),
                $app->make(\App\Modules\Simulation\Core\Domain\Pipelines\Steps\MutateGenomeStep::class),
                $app->make(\App\Modules\Simulation\Core\Domain\Pipelines\Steps\PreCreateInjectionStep::class),
                $app->make(\App\Modules\Simulation\Core\Domain\Pipelines\Steps\CreateUniverseStep::class),
                $app->make(\App\Modules\Simulation\Core\Domain\Pipelines\Steps\InheritAxiomsStep::class),
                $app->make(\App\Modules\Simulation\Core\Domain\Pipelines\Steps\FinalizeSpawnStep::class),
            ]);
        });

        // Simulation Runtime: Tick Scheduler + Pipeline + Orchestrator
        $this->app->singleton(\App\Modules\Simulation\Core\Runtime\Contracts\TickSchedulerInterface::class, \App\Modules\Simulation\Core\Runtime\PhaseScheduler::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Runtime\SimulationTickPipeline::class, function ($app) {
            $scheduler = $app->make(\App\Modules\Simulation\Core\Runtime\Contracts\TickSchedulerInterface::class);
            $stageMap = [
                // Phase: environment
                'rule'         => \App\Modules\Simulation\Core\Runtime\Stages\RuleStage::class,
                'environment'  => \App\Modules\Simulation\Core\Runtime\Stages\EnvironmentStage::class,
                'physics'      => \App\Modules\Simulation\Core\Runtime\Stages\PhysicsStage::class,

                // Phase: life
                'population'   => \App\Modules\Simulation\Core\Runtime\Stages\PopulationStage::class,
                'ecology'      => \App\Modules\Simulation\Core\Runtime\Stages\EcologyStage::class,

                // Phase: mind
                'vector_actor' => \App\Modules\Simulation\Core\Runtime\Stages\VectorizedActorStage::class,
                'actor'        => \App\Modules\Simulation\Core\Runtime\Stages\ActorStage::class,

                // Phase: social
                'civilization' => \App\Modules\Simulation\Core\Runtime\Stages\CivilizationStage::class,
                'field'        => \App\Modules\Simulation\Core\Runtime\Stages\CivilizationFieldStage::class,
                'economy'      => \App\Modules\Simulation\Core\Runtime\Stages\EconomyStage::class,
                'politics'     => \App\Modules\Simulation\Core\Runtime\Stages\PoliticsStage::class,
                'culture'      => \App\Modules\Simulation\Core\Runtime\Stages\CultureStage::class,

                // Phase: meta
                'war'          => \App\Modules\Simulation\Core\Runtime\Stages\WarStage::class,
                'meta'         => \App\Modules\Simulation\Core\Runtime\Stages\MetaCosmicStage::class,
            ];
            $stages = [];
            foreach ($scheduler->stageOrder() as $key) {
                if (isset($stageMap[$key])) {
                    $stages[$key] = $app->make($stageMap[$key]);
                }
            }
            return new \App\Modules\Simulation\Core\Runtime\SimulationTickPipeline(
                $scheduler,
                $stages,
                $app->make(\App\Modules\Simulation\Core\Runtime\State\StateManager::class),
                $app->make(\App\Modules\Simulation\Core\Runtime\EventDrivenScheduler::class),
                $app->make(\App\Modules\Simulation\Core\Engines\Biological\AutopoieticEvolutionEngine::class),
                $app->make(\App\Modules\Simulation\Services\Core\RuleMutationService::class),
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\InformationPropagationEngine::class),
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\PowerStructureEngine::class),
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\CulturalInfluenceEngine::class),
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\MythogenesisEngine::class),
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\MeaningEngine::class),
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\KnowledgeEvolutionEngine::class),
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\ThermodynamicPhaseEngine::class),
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\SingularityStabilityEngine::class),
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\AscensionEngine::class),
                $app->make(\App\Modules\Simulation\Services\Ecology\ZenithMetricsService::class),
                $app->make(\App\Modules\Simulation\Core\Engines\Meta\CausalHistoryEngine::class),
                $app->make(\App\Modules\Simulation\Core\Runtime\WorldKernel::class),
                $app->make(\App\Modules\Narrative\Services\NarrativeEngine::class),
                $app->make(\App\Modules\Narrative\Services\NarrativeQueueManager::class)
            );
        });
        $this->app->singleton(\App\Modules\Simulation\Core\Runtime\SimulationTickOrchestrator::class);

        // State cache (optional) — Phase 2 §2.3
        $this->app->bind(\App\Modules\Simulation\Core\Contracts\StateCacheInterface::class, function ($app) {
            $driver = \config('worldos.state_cache.driver', 'null');
            if ($driver === 'redis') {
                return new \App\Modules\Simulation\Core\StateCache\RedisStateCache(
                    \config('worldos.state_cache.key_prefix', 'worldos:'),
                    \config('worldos.state_cache.ttl_seconds', 300)
                );
            }
            return $app->make(\App\Modules\Simulation\Core\StateCache\NullStateCache::class);
        });

        // Snapshot archive (S3/MinIO optional) — Doc §10
        $this->app->bind(\App\Modules\Simulation\Core\Contracts\SnapshotArchiveInterface::class, function ($app) {
            $driver = \config('worldos.snapshot.archive_driver', 'null');
            if ($driver === 's3') {
                return new \App\Modules\Simulation\Core\SnapshotArchive\S3SnapshotArchive(
                    \config('worldos.snapshot.archive.disk', 's3'),
                    \config('worldos.snapshot.archive.prefix', 'worldos/snapshots')
                );
            }
            return $app->make(\App\Modules\Simulation\Core\SnapshotArchive\NullSnapshotArchive::class);
        });

        // Phase 2: Simulation Supervisor
        $this->app->singleton(\App\Modules\Simulation\Core\Supervisor\EngineDriver::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Supervisor\StateSynchronizer::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Supervisor\SnapshotManager::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Supervisor\EventDispatcher::class);
        $this->app->singleton(\App\Modules\Simulation\Core\Supervisor\RuntimePipeline::class, function ($app) {
            $handlers = [
                $app->make(\App\Modules\Simulation\Core\Supervisor\Handlers\CognitivePostSnapshotHandler::class),
                $app->make(\App\Modules\Simulation\Core\Supervisor\Handlers\CollapsePostSnapshotHandler::class),
                $app->make(\App\Modules\Simulation\Core\Supervisor\Handlers\SocialGraphPostSnapshotHandler::class),
                $app->make(\App\Modules\Simulation\Core\Supervisor\Handlers\DemographicRatesPostSnapshotHandler::class),
                $app->make(\App\Modules\Simulation\Core\Supervisor\Handlers\UrbanStressAgriculturePostSnapshotHandler::class),
                $app->make(\App\Modules\Simulation\Core\Supervisor\Handlers\KnowledgeGraphPostSnapshotHandler::class),
                $app->make(\App\Modules\Simulation\Core\Supervisor\Handlers\CivilizationDiscoveryPostSnapshotHandler::class),
                $app->make(\App\Modules\Simulation\Core\Supervisor\Handlers\SelfImprovingPostSnapshotHandler::class),
                $app->make(\App\Modules\Simulation\Core\Supervisor\Handlers\RawGenerationPostSnapshotHandler::class),
                // RuleVm already handled in RuleStage
            ];
            return new \App\Modules\Simulation\Core\Supervisor\RuntimePipeline(
                $app->make(\App\Modules\Simulation\Core\Runtime\SimulationTickOrchestrator::class),
                $handlers
            );
        });
        $this->app->singleton(\App\Modules\Simulation\Core\Supervisor\SimulationSupervisor::class);
    }
}
