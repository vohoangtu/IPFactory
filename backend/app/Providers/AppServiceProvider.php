<?php

namespace App\Providers;

use App\Contracts\LlmNarrativeClientInterface;
use App\Contracts\SimulationEngineClientInterface;
use App\Contracts\UniverseEvaluatorInterface;
use App\Modules\Narrative\Services\GatewayNarrativeService;
use App\Modules\Simulation\Repositories\UniverseSnapshotRepository;
use App\Modules\Simulation\Services\Core\HttpSimulationEngineClient;
use App\Modules\Simulation\Services\Core\StubSimulationEngineClient;
use App\Modules\Simulation\Services\Core\GrpcSimulationEngineClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use App\Broadcasting\CentrifugoBroadcaster;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Ensure Monolog handler base classes load before config/logging.php references StreamHandler (fixes "AbstractHandler not found" on seed)
        class_exists(\Monolog\Handler\AbstractHandler::class, true);

        $this->app->singleton(SimulationEngineClientInterface::class, function ($app) {
            $url = (string) config('worldos.simulation_engine_grpc_url', '');
            if ($url !== '') {
                if (str_starts_with($url, 'grpc://')) {
                    $parsed = parse_url($url);
                    $host = $parsed['host'] ?? 'localhost';
                    $port = $parsed['port'] ?? 50051;

                    // Chỉ khởi tạo GrpcClient nếu extension đã được cài đặt
                    if (class_exists(\Grpc\ChannelCredentials::class)) {
                        return new GrpcSimulationEngineClient($host . ':' . $port);
                    }
                    Log::error("gRPC extension is not installed. Falling back to HTTP.");
                    $url = 'http://' . $host . ':50052';
                }

                if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || !str_contains($url, '://')) {
                    $finalUrl = str_contains($url, '://') ? $url : 'http://' . $url;
                    
                    // If port is 50051 (gRPC default) but we are using HTTP client, suggest 50052
                    if (str_contains($finalUrl, ':50051') && !class_exists(\Grpc\ChannelCredentials::class)) {
                        $finalUrl = str_replace(':50051', ':50052', $finalUrl);
                    }
                    
                    return new HttpSimulationEngineClient($finalUrl);
                }
            }
            return new StubSimulationEngineClient;
        });
        $this->app->singleton(UniverseSnapshotRepository::class);
        $this->app->singleton(\App\Modules\Simulation\Services\Core\ObserverService::class);
        $this->app->singleton(\App\Modules\Intelligence\Services\AI\MemoryService::class);
        $this->app->bind(
            \App\Contracts\GraphProviderInterface::class,
            \App\Modules\SocialGraph\Services\RelationalGraphProvider::class
        );
        $this->app->singleton(LlmNarrativeClientInterface::class, GatewayNarrativeService::class);

        // Narrative Engine: Strategy registry + pipeline (Event Aggregator → PromptBuilder → Generator → Writer)
        $this->app->singleton(\App\Modules\Narrative\Services\NarrativeStrategyRegistry::class, function ($app) {
            $registry = new \App\Modules\Narrative\Services\NarrativeStrategyRegistry();
            $registry->register($app->make(\App\Modules\Narrative\Services\Strategies\DeathNarrativeStrategy::class));
            $registry->register($app->make(\App\Modules\Narrative\Services\Strategies\RebirthNarrativeStrategy::class));
            $registry->register($app->make(\App\Modules\Narrative\Services\Strategies\ParadoxNarrativeStrategy::class));
            $registry->register($app->make(\App\Modules\Narrative\Services\Strategies\AnomalyNarrativeStrategy::class));
            $registry->register($app->make(\App\Modules\Narrative\Services\Strategies\LegacyNarrativeStrategy::class));
            $registry->register($app->make(\App\Modules\Narrative\Services\Strategies\NanoMagicStrategy::class));
            return $registry;
        });
        $this->app->singleton(\App\Modules\Narrative\Services\NarrativeCache::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Broadcast::extend('centrifugo', function ($app) {
            return new CentrifugoBroadcaster;
        });

        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Simulation\Events\UniverseSimulationPulsed::class,
            \App\Modules\Simulation\Listeners\ProcessMaterialLifecycle::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Simulation\Events\UniverseSimulationPulsed::class,
            \App\Modules\Narrative\Listeners\GenerateNarrative::class
        );
        // ProcessInstitutionalFramework (SupremeEntity, Institutions) must run BEFORE EvaluateSimulationResult
        // so Eval can merge cosmic impact into metrics and save once; no listener after Eval should write snapshot.
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Simulation\Events\UniverseSimulationPulsed::class,
            \App\Modules\Institutions\Listeners\ProcessInstitutionalFramework::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Simulation\Events\UniverseSimulationPulsed::class,
            \App\Modules\Simulation\Listeners\EvaluateSimulationResult::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Simulation\Events\UniverseSimulationPulsed::class,
            \App\Modules\Simulation\Listeners\RecordMaterialIdentityTransition::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Simulation\Events\UniverseSimulationPulsed::class,
            \App\Modules\Simulation\Listeners\StagnationDetectorListener::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Simulation\Events\UniverseSimulationPulsed::class,
            \App\Modules\SocialGraph\Listeners\SyncToGraph::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Simulation\Events\UniverseSimulationPulsed::class,
            \App\Modules\Intelligence\Listeners\ProcessIntelligenceEvolution::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Simulation\Events\UniverseSimulationPulsed::class,
            \App\Modules\Simulation\Listeners\PublishSimulationAdvancedToKafka::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Simulation\Events\SimulationEventOccurred::class,
            \App\Modules\SocialGraph\Listeners\SyncWorldEventToGraph::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Simulation\Events\SimulationEventOccurred::class,
            \App\Modules\Simulation\Listeners\PublishRuleFiredToKafka::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Simulation\Events\RuleProposed::class,
            \App\Modules\Simulation\Listeners\PersistRuleProposal::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Simulation\Events\SimulationEventOccurred::class,
            \App\Modules\Simulation\Listeners\SyncWorldEventToCausalityGraph::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Simulation\Events\SimulationEventOccurred::class,
            \App\Modules\Narrative\Listeners\RecordHistoricalFact::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Simulation\Events\SimulationEventOccurred::class,
            \App\Modules\Simulation\Listeners\ApplyMemoryResonance::class
        );
    }
}
