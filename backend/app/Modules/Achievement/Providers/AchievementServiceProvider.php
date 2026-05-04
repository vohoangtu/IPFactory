<?php

namespace App\Modules\Achievement\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AchievementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/achievement.php', 'achievement');
    }

    public function boot(): void
    {
        Route::group([
            'prefix' => 'api',
            'middleware' => 'api',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        });
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
