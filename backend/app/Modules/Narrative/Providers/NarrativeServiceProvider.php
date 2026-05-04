<?php

namespace App\Modules\Narrative\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Modules\Narrative\Services\NarrativeEngine;
use App\Modules\Narrative\Services\StateExtractorDSL;
use App\Modules\Narrative\Services\SignalExtractor;
use App\Modules\Narrative\Services\StateMutationEngine;
use App\Modules\Narrative\Services\ChronicleSynthesisEngine;
use App\Modules\Narrative\Services\UniverseHistoryGenerator;
use App\Modules\Narrative\Repositories\ChronicleMemoryRepository;
use App\Contracts\LlmNarrativeClientInterface;
use App\Modules\Narrative\Services\GatewayNarrativeService;

class NarrativeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StateExtractorDSL::class);
        $this->app->singleton(SignalExtractor::class);
        $this->app->singleton(StateMutationEngine::class);
        $this->app->singleton(ChronicleMemoryRepository::class);
        $this->app->singleton(ChronicleSynthesisEngine::class);
        $this->app->singleton(UniverseHistoryGenerator::class);
        
        $this->app->singleton(\App\Modules\Narrative\Contracts\ArtifactRepositoryInterface::class, \App\Modules\Narrative\Repositories\ArtifactEloquentRepository::class);
        $this->app->singleton(\App\Modules\Narrative\Contracts\ChronicleRepositoryInterface::class, \App\Modules\Narrative\Repositories\ChronicleEloquentRepository::class);
        $this->app->singleton(\App\Modules\Narrative\Contracts\MythScarRepositoryInterface::class, \App\Modules\Narrative\Repositories\MythScarEloquentRepository::class);
        $this->app->singleton(\App\Modules\Narrative\Contracts\DemiurgeRepositoryInterface::class, \App\Modules\Narrative\Repositories\DemiurgeEloquentRepository::class);

        $this->app->singleton(NarrativeEngine::class);
        $this->app->singleton(LlmNarrativeClientInterface::class, GatewayNarrativeService::class);

        // Batch 8: Moved Services
        $this->app->singleton(\App\Modules\Narrative\Services\AdaptivePulseScheduler::class);
        $this->app->singleton(\App\Modules\Narrative\Services\NarrativeQueueManager::class);
        $this->app->singleton(\App\Modules\Narrative\Services\EraDetector::class);
        $this->app->singleton(\App\Modules\Narrative\Services\HistoricalFactEngine::class);
        $this->app->singleton(\App\Modules\Narrative\Services\EventTriggerMapper::class);
        $this->app->singleton(\App\Modules\Narrative\Services\TraitMapper::class);
        $this->app->singleton(\App\Modules\Narrative\Services\NarrativeMemoryGraphService::class);
        $this->app->singleton(\App\Modules\Narrative\Services\CausalTrajectoryFulfillment::class);
        $this->app->singleton(\App\Modules\Narrative\Services\ReligionSpreadEngine::class);
        $this->app->singleton(\App\Modules\Narrative\Services\NarrativeChapterEngine::class);
        $this->app->singleton(\App\Modules\Narrative\Services\NarrativeGeneratorService::class);
        $this->app->singleton(\App\Modules\Narrative\Services\NarrativeAiService::class);
        $this->app->singleton(\App\Modules\Narrative\Services\MeaningLoopService::class);
        $this->app->singleton(\App\Modules\Narrative\Services\PerspectiveEngine::class);

        $this->app->singleton(\App\Modules\Narrative\Services\EraNarrativeEngine::class);
        $this->app->singleton(\App\Modules\Narrative\Services\CivilizationChronicleEngine::class);
        $this->app->singleton(\App\Modules\Narrative\Services\MythologyEngine::class);
        $this->app->singleton(\App\Modules\Narrative\Services\ReligionGenerator::class);
        $this->app->singleton(\App\Modules\Narrative\Services\ReligionSeedDetector::class);
        $this->app->singleton(\App\Modules\Narrative\Services\FuturePredictor::class);
        $this->app->singleton(\App\Modules\Narrative\Services\CausalTrajectoryGenerator::class);
        $this->app->singleton(\App\Modules\Narrative\Services\LegendEngine::class);
        $this->app->singleton(\App\Modules\Narrative\Services\OmenIntegrationService::class);
    }

    public function boot(): void
    {
        Route::group([
            'prefix' => 'api',
            'middleware' => 'api',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        });

        $events = $this->app->make(\Illuminate\Contracts\Events\Dispatcher::class);
        $events->listen(
            \App\Modules\Simulation\Core\Events\ActorBornEvent::class,
            [\App\Modules\Narrative\Listeners\ChronicleLifeEventListener::class, 'handleActorBorn']
        );
        $events->listen(
            \App\Modules\Simulation\Core\Events\ActorDiedEvent::class,
            [\App\Modules\Narrative\Listeners\ChronicleLifeEventListener::class, 'handleActorDied']
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Modules\Narrative\Console\Commands\ArchiveAncientEras::class,
                \App\Modules\Narrative\Console\Commands\GenerateHistorianCommand::class,
                \App\Modules\Narrative\Console\Commands\NarrativeHistoryBookCommand::class,
                \App\Modules\Narrative\Console\Commands\WeaveNarrativesCommand::class,
                \App\Modules\Narrative\Console\Commands\WorldosZenithAscentCommand::class,
            ]);
        }
    }
}
