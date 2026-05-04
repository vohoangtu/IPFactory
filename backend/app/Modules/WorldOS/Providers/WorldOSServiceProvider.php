<?php

namespace App\Modules\WorldOS\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

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
    }
}
