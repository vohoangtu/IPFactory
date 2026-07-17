<?php

namespace App\Modules\Simulation\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SimulationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(SimulationConfigServiceProvider::class);
        $this->app->register(\App\Modules\Narrative\Providers\NarrativeServiceProvider::class);

        // Register sub-providers
        $this->app->register(RepositoryServiceProvider::class);
        $this->app->register(EngineServiceProvider::class);
        $this->app->register(KernelServiceProvider::class);
        $this->app->register(PipelineServiceProvider::class);
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Simulation\Events\EpochTransitioned::class,
            \App\Modules\Simulation\Listeners\HandleEpochTransition::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\SocialGraph\Events\CelebrityEmerged::class,
            [\App\Modules\Simulation\Listeners\GenerateAssetListener::class, 'handleCelebrity']
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\World\Events\ArtifactDiscovered::class,
            [\App\Modules\Simulation\Listeners\GenerateAssetListener::class, 'handleArtifact']
        );

        Route::group([
            'prefix' => 'api',
            'middleware' => 'api',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Modules\Simulation\Console\Commands\AdvanceSimulationCommand::class,
                \App\Modules\Simulation\Console\Commands\AutonomicPulseCommand::class,
                \App\Modules\Simulation\Console\Commands\DeployRuleProposalCommand::class,
                \App\Modules\Simulation\Console\Commands\WorldOSRunContinuousCommand::class,
                \App\Modules\Simulation\Console\Commands\WorldosAutonomicPulseCommand::class,
                \App\Modules\Simulation\Console\Commands\WorldosBenchmarkTickCommand::class,
                \App\Modules\Simulation\Console\Commands\WorldosCalibrationCheckCommand::class,
                \App\Modules\Simulation\Console\Commands\WorldosReplayCommand::class,
                \App\Modules\Simulation\Console\Commands\WorldosSimulationBatchCommand::class,
                \App\Modules\Simulation\Console\Commands\WorldosSimulationMetricsCommand::class,
                \App\Modules\Simulation\Console\Commands\SeedMaterialsCommand::class,
                \App\Modules\Simulation\Console\Commands\KafkaEventStreamConsumeCommand::class,
                \App\Modules\Simulation\Console\Commands\ResetWorldOS::class,
                \App\Modules\Simulation\Console\Commands\RunDemoScenario::class,
                \App\Modules\Simulation\Console\Commands\WorldosDemoCommand::class,
                \App\Modules\Simulation\Console\Commands\WorldosEnginesCommand::class,
                \App\Modules\Simulation\Console\Commands\WorldosEngineProductsCommand::class,
                \App\Modules\Simulation\Console\Commands\WorldosMetricsReportCommand::class,
                \App\Modules\Simulation\Console\Commands\WorldosSimCommand::class,
                \App\Modules\Simulation\Console\Commands\StressTestCommand::class,
                \App\Modules\Simulation\Console\Commands\HealthCheckCommand::class,
            ]);
        }
    }
}
