<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\Repositories\UniverseRepositoryInterface::class,
            \App\Modules\Simulation\Repositories\UniverseRepository::class
        );
        $this->app->bind(
            \App\Contracts\Repositories\BranchEventRepositoryInterface::class,
            \App\Modules\Simulation\Repositories\BranchEventRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
