<?php

namespace App\Modules\Psychology\Providers;

use App\Modules\Psychology\Dsl\BehaviorDslLoader;
use App\Modules\Psychology\Dsl\ExpressionEngine;
use App\Modules\Psychology\Services\ConflictDetector;
use App\Modules\Psychology\Services\ConflictResolver;
use App\Modules\Psychology\Services\DecisionEngine;
use App\Modules\Psychology\Services\GoalGenerator;
use App\Modules\Psychology\Services\ImpulseGenerator;
use App\Modules\Psychology\Services\MeaningEngine;
use App\Modules\Psychology\Services\MemoryInfluenceAnalyzer;
use App\Modules\Psychology\Services\StateEvolutionService;
use App\Modules\Simulation\Core\Engines\Social\PsychologyEngine;
use Illuminate\Support\ServiceProvider;

class PsychologyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // DSL Layer
        $this->app->singleton(ExpressionEngine::class);
        $this->app->singleton(BehaviorDslLoader::class);

        // Services
        $this->app->singleton(MeaningEngine::class);
        $this->app->singleton(ImpulseGenerator::class);
        $this->app->singleton(ConflictDetector::class);
        $this->app->singleton(ConflictResolver::class);
        $this->app->singleton(StateEvolutionService::class);
        $this->app->singleton(MemoryInfluenceAnalyzer::class);
        $this->app->singleton(GoalGenerator::class);
        $this->app->singleton(DecisionEngine::class);
        
        // Phase 2 Services
        $this->app->singleton(\App\Modules\Psychology\Services\SocialMemoryService::class);
        $this->app->singleton(\App\Modules\Psychology\Services\IdentityEvolutionService::class);
        $this->app->singleton(\App\Modules\Psychology\Services\ReputationResolver::class);
        $this->app->singleton(\App\Modules\Psychology\Services\JungianBehaviorAdapter::class);

        // Phase 3 Services
        $this->app->singleton(\App\Modules\Psychology\Services\CulturePropagationService::class);
        $this->app->singleton(\App\Modules\Psychology\Services\MythGenerator::class);
        $this->app->singleton(\App\Modules\Psychology\Services\GoapPlanner::class);

        // Engine (resolved with all dependencies auto-injected)
        $this->app->singleton(PsychologyEngine::class);
    }

    public function boot(): void
    {
        // Publish resources for customization
        $this->publishes([
            __DIR__ . '/../../../../resources/worldos_psychology' => resource_path('worldos_psychology'),
        ], 'psychology-dsl');
    }
}

