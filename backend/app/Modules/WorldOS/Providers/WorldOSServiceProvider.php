<?php

namespace App\Modules\WorldOS\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Event;

class WorldOSServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Route::group([
            'prefix' => 'api',
            'middleware' => 'api',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        });

        Event::listen([
            \App\Modules\Simulation\Events\EpochTransitioned::class,
            \App\Modules\Simulation\Events\AnomalyDetected::class,
            \App\Modules\Simulation\Events\AutopoiesisMutationApplied::class,
            \App\Modules\World\Events\ArtifactDiscovered::class,
            \App\Modules\SocialGraph\Events\CelebrityEmerged::class,
            \App\Modules\Narrative\Events\HistoricalEpochShifted::class,
        ], \App\Modules\WorldOS\Listeners\PersistWorldEventBroadcast::class);
    }
}
