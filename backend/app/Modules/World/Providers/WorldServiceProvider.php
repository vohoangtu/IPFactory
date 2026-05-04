<?php

namespace App\Modules\World\Providers;

use Illuminate\Support\ServiceProvider;

class WorldServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register Actions
        $this->app->singleton(\App\Modules\World\Actions\Economics\EvaluateTradeAction::class);
        $this->app->singleton(\App\Modules\World\Actions\Economics\HarvestResourceAction::class);
        
        // Register Repositories
        $this->app->singleton(\App\Modules\World\Contracts\InventoryRepositoryInterface::class, \App\Modules\World\Repositories\CacheInventoryRepository::class);
        $this->app->singleton(\App\Modules\World\Contracts\ResourceRepositoryInterface::class, \App\Modules\World\Repositories\EloquentResourceRepository::class);

        // Register Material Services
        $this->app->singleton(\App\Modules\World\Services\PressureResolver::class);
        $this->app->singleton(\App\Modules\World\Services\MaterialReactionEngine::class);
    }

    public function boot(): void
    {
        //
    }
}
