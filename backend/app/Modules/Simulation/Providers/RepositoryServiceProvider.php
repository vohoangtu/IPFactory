<?php

namespace App\Modules\Simulation\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Simulation\Contracts\RelicRepositoryInterface;
use App\Modules\Simulation\Repositories\RelicEloquentRepository;
use App\Modules\Simulation\Contracts\TrajectoryRepositoryInterface;
use App\Modules\Simulation\Repositories\TrajectoryEloquentRepository;
use App\Modules\Simulation\Contracts\UniverseRepositoryInterface;
use App\Modules\Simulation\Repositories\UniverseEloquentRepository;
use App\Modules\Simulation\Vocation\Contracts\VocationRepositoryInterface;
use App\Modules\Simulation\Vocation\Repositories\VocationEloquentRepository;
use App\Modules\Simulation\Vocation\Contracts\SkillRepositoryInterface;
use App\Modules\Simulation\Vocation\Repositories\SkillEloquentRepository;
use App\Modules\Simulation\Vocation\Contracts\ActorMasteryRepositoryInterface;
use App\Modules\Simulation\Vocation\Repositories\ActorMasteryEloquentRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Simulation repositories
        $this->app->bind(RelicRepositoryInterface::class, RelicEloquentRepository::class);
        $this->app->bind(TrajectoryRepositoryInterface::class, TrajectoryEloquentRepository::class);
        $this->app->bind(UniverseRepositoryInterface::class, UniverseEloquentRepository::class);
        $this->app->bind(\App\Modules\Simulation\Contracts\WorldRepositoryInterface::class, \App\Modules\Simulation\Repositories\WorldEloquentRepository::class);
        $this->app->bind(\App\Modules\Simulation\Contracts\SnapshotRepositoryInterface::class, \App\Modules\Simulation\Repositories\SnapshotEloquentRepository::class);
        $this->app->bind(\App\Modules\Simulation\Contracts\BranchEventRepositoryInterface::class, \App\Modules\Simulation\Repositories\BranchEventRepository::class);
        $this->app->bind(\App\Contracts\Repositories\BranchEventRepositoryInterface::class, \App\Modules\Simulation\Repositories\BranchEventRepository::class);

        // Vocation V1 Repository Bindings
        $this->app->bind(VocationRepositoryInterface::class, VocationEloquentRepository::class);
        $this->app->bind(SkillRepositoryInterface::class, SkillEloquentRepository::class);
        $this->app->bind(ActorMasteryRepositoryInterface::class, ActorMasteryEloquentRepository::class);
    }
}
