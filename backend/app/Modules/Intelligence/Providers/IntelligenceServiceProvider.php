<?php

namespace App\Modules\Intelligence\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Modules\Intelligence\Contracts\ActorRepositoryInterface;
use App\Modules\Intelligence\Repositories\ActorEloquentRepository;
use App\Modules\Intelligence\Contracts\AgentDecisionRepositoryInterface;
use App\Modules\Intelligence\Repositories\AgentDecisionEloquentRepository;
use App\Modules\Intelligence\Contracts\AiMemoryRepositoryInterface;
use App\Modules\Intelligence\Repositories\AiMemoryEloquentRepository;

use App\Modules\Intelligence\Entities\Archetypes\Warlord;
use App\Modules\Intelligence\Entities\Archetypes\Technocrat;
use App\Modules\Intelligence\Entities\Archetypes\RogueAI;
use App\Modules\Intelligence\Entities\Archetypes\Archmage;
use App\Modules\Intelligence\Entities\Archetypes\VillageElder;
use App\Modules\Intelligence\Entities\Archetypes\TribalLeader;
use App\Modules\Intelligence\Services\ActorRegistry;
use App\Modules\Intelligence\Services\CivilizationAttractorEngine;

class IntelligenceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(ActorRepositoryInterface::class, ActorEloquentRepository::class);
        $this->app->bind(AgentDecisionRepositoryInterface::class, AgentDecisionEloquentRepository::class);
        $this->app->bind(AiMemoryRepositoryInterface::class, AiMemoryEloquentRepository::class);

        // Tag archetype classes for auto-discovery
        $this->app->tag([
            Warlord::class,
            Technocrat::class,
            RogueAI::class,
            Archmage::class,
            VillageElder::class,
            TribalLeader::class,
        ], 'archetype');

        // ActorRegistry with auto-discovery
        $this->app->singleton(ActorRegistry::class, function ($app) {
            return new ActorRegistry($app->tagged('archetype'));
        });

        // Civilization Attractor Engine (singleton — stateless)
        $this->app->singleton(CivilizationAttractorEngine::class);

        $this->app->singleton(\App\Modules\Intelligence\Services\ActorEvolutionService::class);
        $this->app->singleton(\App\Modules\Intelligence\Services\AgentAutonomyService::class);
        $this->app->singleton(\App\Modules\Intelligence\Services\AI\AiConfigManager::class);
        $this->app->singleton(\App\Modules\Intelligence\Services\AI\AiResponseNormalizer::class);
        $this->app->singleton(\App\Modules\Intelligence\Services\AI\AiProviderRouter::class);
        $this->app->singleton(\App\Modules\Intelligence\Services\AI\AiGateway::class);
        $this->app->singleton(\App\Modules\Intelligence\Services\AI\AnalyticalAiService::class);


        $this->app->singleton(\App\Modules\Intelligence\Services\AI\SearchAiService::class);
        $this->app->singleton(\App\Modules\Intelligence\Services\ActorIdentityService::class);
    }

    public function boot(): void
    {
        Route::group([
            'prefix' => 'api',
            'middleware' => 'api',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        });

        $events = $this->app->make(\Illuminate\Contracts\Events\Dispatcher::class);
        $events->listen(
            \App\Modules\Simulation\Core\Events\ActorBornEvent::class,
            \App\Modules\Intelligence\Listeners\ActorBornEventListener::class
        );
        $events->listen(
            \App\Modules\Simulation\Core\Events\ActorDiedEvent::class,
            \App\Modules\Intelligence\Listeners\ActorDiedEventListener::class
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Modules\Intelligence\Console\Commands\ArenaRunCommand::class,
                \App\Modules\Intelligence\Console\Commands\ArenaSimulateCommand::class,
                \App\Modules\Intelligence\Console\Commands\DiscoveryRunGenerationCommand::class,
                \App\Modules\Intelligence\Console\Commands\RunAiAnalysis::class,
                \App\Modules\Intelligence\Console\Commands\KafkaActorStateConsumeCommand::class,
            ]);
        }
    }
}
