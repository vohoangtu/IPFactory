<?php

namespace App\Modules\SocialGraph\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Modules\SocialGraph\Contracts\CivilizationRepositoryInterface;
use App\Modules\SocialGraph\Services\Neo4jSocialSyncer;

class SocialGraphServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Neo4jSocialSyncer::class, function ($app) {
            return new Neo4jSocialSyncer();
        });
    }

    public function boot(): void
    {
        Route::group([
            'prefix' => 'api',
            'middleware' => 'api',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Modules\SocialGraph\Console\Commands\Neo4jSyncCommand::class,
            ]);
        }

        $events = $this->app->make(\Illuminate\Contracts\Events\Dispatcher::class);
        $events->listen(
            \App\Modules\Simulation\Core\Events\ActorBornEvent::class,
            [\App\Modules\SocialGraph\Listeners\SyncSocialGraphListener::class, 'handleActorBorn']
        );
        $events->listen(
            \App\Modules\Simulation\Core\Events\ActorDiedEvent::class,
            [\App\Modules\SocialGraph\Listeners\SyncSocialGraphListener::class, 'handleActorDied']
        );
    }
}
