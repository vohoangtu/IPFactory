<?php

namespace App\Modules\Knowledge\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class KnowledgeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__ . '/../routes/api.php');
    }

    public function register(): void
    {
        //
    }
}
