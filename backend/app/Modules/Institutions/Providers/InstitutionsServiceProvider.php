<?php

namespace App\Modules\Institutions\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Institutions\Contracts\InstitutionalRepositoryInterface;
use App\Modules\Institutions\Repositories\InstitutionalEloquentRepository;
use App\Modules\Institutions\Contracts\SocialContractRepositoryInterface;
use App\Modules\Institutions\Repositories\SocialContractEloquentRepository;
use App\Modules\Institutions\Contracts\SupremeEntityRepositoryInterface;
use App\Modules\Institutions\Repositories\SupremeEntityEloquentRepository;

class InstitutionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InstitutionalRepositoryInterface::class, InstitutionalEloquentRepository::class);
        $this->app->bind(SocialContractRepositoryInterface::class, SocialContractEloquentRepository::class);
        $this->app->bind(SupremeEntityRepositoryInterface::class, SupremeEntityEloquentRepository::class);
        
        $this->app->singleton(\App\Modules\Institutions\Services\DiplomaticResonanceEngine::class);
        $this->app->singleton(\App\Modules\Institutions\Services\InstitutionEvolutionService::class);
        $this->app->singleton(\App\Modules\Institutions\Services\SupremeEntityEvolutionService::class);
        $this->app->singleton(\App\Modules\Institutions\Services\WorldEdictEngine::class);
        $this->app->singleton(\App\Modules\Institutions\Services\GreatFilterEngine::class);
        $this->app->singleton(\App\Modules\Institutions\Services\OmegaPointEngine::class);
        $this->app->singleton(\App\Modules\Institutions\Services\AscensionEngine::class);
        $this->app->singleton(\App\Modules\Institutions\Services\ZoneConflictEngine::class);
        $this->app->singleton(\App\Modules\Institutions\Services\SocialDynamicsEngine::class);

        $this->app->bind(\App\Modules\Institutions\Actions\DetectEmergentCivilizationsAction::class);
        $this->app->bind(\App\Modules\Institutions\Actions\SpawnSupremeEntityAction::class);
        $this->app->bind(\App\Modules\Institutions\Actions\AscendHeroAction::class);
    }

    public function boot(): void
    {
        // Future: Register routes if needed
    }
}
