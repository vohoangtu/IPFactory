<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Intelligence\Models\AiKeyPool;
use App\Modules\Intelligence\Services\AI\AiGateway;
use App\Services\CircuitBreaker;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * NarrativeLoomService: Laravel client for the Python NarrativeLoom microservice.
 * Runtime provider selection is sourced from AiGateway so Loom follows pool/direct routing too.
 */
class NarrativeLoomService
{
    protected string $baseUrl;
    protected int $timeout;
    protected CircuitBreaker $circuitBreaker;

    public function __construct(
        protected AiGateway $aiGateway
    ) {
        $this->baseUrl = rtrim((string) config('services.loom.url', 'http://narrative_loom:8001'), '/');
        $this->timeout = (int) config('services.loom.timeout', 600);
        $this->circuitBreaker = new CircuitBreaker('narrative_loom', 3, 60);
    }

    /**
     * Weave multiple chronicles into a single high-quality prose via Narrative Loom.
     */
    public function weave(int $worldId, ?int $tickStart = null, ?int $tickEnd = null): array
    {
        if (! $this->circuitBreaker->isAvailable()) {
            Log::warning('NarrativeLoom: circuit breaker OPEN — skipping weave call');

            return ['ok' => false, 'error' => 'NarrativeLoom circuit breaker is open (service temporarily unavailable)'];
        }

        $world = \App\Modules\World\Models\World::find($worldId);
        $genre = $world ? ($world->current_genre ?? $world->base_genre) : 'generic';

        // Tạm thởi bỏ qua AI key requirement để test
        try {
            $runtime = $this->aiGateway->runtimeProfileForFeature('narrative');
        } catch (\Throwable $e) {
            Log::warning('NarrativeLoom: AI key pool exhausted, using default runtime (AI Pool)', ['error' => $e->getMessage()]);
            $runtime = [
                'provider' => 'local',
                'model' => 'qwen3.5-9b-uncensored-hauhaucs-aggressive',
            ];
        }
        $keyEntry = $runtime['key_entry'] ?? null;

        $whispers = \App\Modules\Narrative\Models\Narrative::where('is_active', true)
            ->where('universe_id', '!=', $world->universes()->first()?->id)
            ->where('virality', '>', 0.7)
            ->limit(3)
            ->pluck('story')
            ->toArray();

        $payload = [
            'world_id' => $worldId,
            'world_era' => $world->civilization_era ?? 'genesis',
            'tick_start' => $tickStart,
            'tick_end' => $tickEnd,
            'genre' => $genre,
            'power_system' => $world->power_system_type ?? null,
            'whispers' => $whispers,
            'ai_runtime' => $this->buildRuntimePayload($runtime),
        ];

        Log::info('NarrativeLoom: weave started', [
            'world_id' => $worldId,
            'tick_start' => $tickStart,
            'tick_end' => $tickEnd,
            'provider' => $runtime['provider'] ?? null,
            'model' => $runtime['model'] ?? null,
        ]);

        try {
            /** @var Response $response */
            $response = Http::timeout($this->timeout)->post("{$this->baseUrl}/weave-chronicles", $payload);

            if ($response->successful()) {
                $this->reportRuntimeUsage($keyEntry);
                $this->circuitBreaker->recordSuccess();
                $data = $response->json();
                Log::info('NarrativeLoom: weave completed', [
                    'world_id' => $worldId,
                    'tick_start' => $tickStart,
                    'tick_end' => $tickEnd,
                    'provider' => $runtime['provider'] ?? null,
                    'model' => $runtime['model'] ?? null,
                    'has_final_prose' => !empty($data['final_prose']),
                ]);
                return $data;
            }

            Log::error('NarrativeLoom: weave failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'provider' => $runtime['provider'] ?? null,
                'model' => $runtime['model'] ?? null,
            ]);
            $this->circuitBreaker->recordFailure();
            $this->reportRuntimeUsage($keyEntry, $this->resolveErrorCodeFromResponse($response));
        } catch (\Throwable $e) {
            Log::error('NarrativeLoom: weave exception: ' . $e->getMessage());
            $this->circuitBreaker->recordFailure();
            $this->reportRuntimeUsage($keyEntry, $this->resolveErrorCodeFromThrowable($e));
        }

        return ['ok' => false, 'error' => 'NarrativeLoom communication failed'];
    }

    /**
     * Get real-time AI decision/intent for a specific actor.
     */
    public function getActorIntent(array $requestData): array
    {
        if (! $this->circuitBreaker->isAvailable()) {
            Log::warning('NarrativeLoom: circuit breaker OPEN — skipping actor-intent call');

            return ['ok' => false, 'error' => 'NarrativeLoom circuit breaker is open'];
        }

        $runtime = $this->aiGateway->runtimeProfileForFeature('decision');
        $keyEntry = $runtime['key_entry'] ?? null;
        $payload = array_merge($requestData, $this->buildRuntimePayload($runtime));

        Log::info('NarrativeLoom: actor-intent started', [
            'actor_id' => $requestData['actor_id'] ?? null,
            'provider' => $runtime['provider'] ?? null,
            'model' => $runtime['model'] ?? null,
        ]);

        try {
            /** @var Response $response */
            $response = Http::timeout(30)->post("{$this->baseUrl}/actor-intent", $payload);

            if ($response->successful()) {
                $this->reportRuntimeUsage($keyEntry);
                $this->circuitBreaker->recordSuccess();
                $data = $response->json();
                Log::info('NarrativeLoom: actor-intent completed', [
                    'actor_id' => $requestData['actor_id'] ?? null,
                    'provider' => $runtime['provider'] ?? null,
                    'model' => $runtime['model'] ?? null,
                    'action' => $data['action'] ?? null,
                    'confidence' => $data['confidence'] ?? null,
                ]);
                return $data;
            }

            $this->circuitBreaker->recordFailure();
            $this->reportRuntimeUsage($keyEntry, $this->resolveErrorCodeFromResponse($response));
        } catch (\Throwable $e) {
            Log::warning('NarrativeLoom: actor-intent failed: ' . $e->getMessage());
            $this->circuitBreaker->recordFailure();
            $this->reportRuntimeUsage($keyEntry, $this->resolveErrorCodeFromThrowable($e));
        }

        return ['ok' => false, 'error' => 'Loom actor-intent unavailable'];
    }

    /**
     * Expose the runtime payload used for Loom calls to other callers that still assemble requests externally.
     *
     * @return array<string, mixed>
     */
    public function runtimePayloadForFeature(string $feature): array
    {
        return $this->buildRuntimePayload($this->aiGateway->runtimeProfileForFeature($feature));
    }

    /**
     * @param array<string, mixed> $runtime
     * @return array<string, mixed>
     */
    protected function buildRuntimePayload(array $runtime): array
    {
        $payload = [
            'provider' => $runtime['provider'] ?? 'local',
        ];

        if (!empty($runtime['model'])) {
            $payload['model_name'] = $runtime['model'];
        }

        if (!empty($runtime['api_key'])) {
            $payload['api_key'] = $runtime['api_key'];
        }

        if (!empty($runtime['base_url'])) {
            $payload['base_url'] = $runtime['base_url'];
        }

        return $payload;
    }

    protected function reportRuntimeUsage(?AiKeyPool $keyEntry, ?int $errorCode = null): void
    {
        if (!$keyEntry) {
            return;
        }

        app(\App\Modules\Intelligence\Actions\ReportKeyUsageAction::class)->handle($keyEntry, $errorCode);
    }

    protected function resolveErrorCodeFromResponse(Response $response): ?int
    {
        $message = strtolower($response->body());

        if ($response->status() === 401
            || str_contains($message, '401')
            || str_contains($message, 'unauthorized')
            || str_contains($message, 'invalid api key')
            || str_contains($message, 'incorrect api key')
            || str_contains($message, 'token expired')) {
            return 401;
        }

        if ($response->status() === 429
            || str_contains($message, '429')
            || str_contains($message, 'rate limit')) {
            return 429;
        }

        return null;
    }

    protected function resolveErrorCodeFromThrowable(\Throwable $e): ?int
    {
        $code = method_exists($e, 'getCode') ? (int) $e->getCode() : null;
        $message = strtolower($e->getMessage());

        if ($code === 401
            || str_contains($message, '401')
            || str_contains($message, 'unauthorized')
            || str_contains($message, 'invalid api key')
            || str_contains($message, 'incorrect api key')
            || str_contains($message, 'token expired')) {
            return 401;
        }

        if ($code === 429 || str_contains($message, '429') || str_contains($message, 'rate limit')) {
            return 429;
        }

        return null;
    }
}
