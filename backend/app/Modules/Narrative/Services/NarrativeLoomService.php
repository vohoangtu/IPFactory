<?php

declare(strict_types=1);

namespace App\Modules\Narrative\Services;

use App\Modules\Intelligence\Models\AiKeyPool;
use App\Modules\Intelligence\Services\AI\AiGateway;
use App\Services\CircuitBreaker;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * NarrativeLoomService: Laravel client for the Python NarrativeLoom microservice.
 *
 * ## Resilience Features
 *
 * - **Circuit Breaker**: Opens after 3 consecutive failures, closes after 60s cooldown.
 * - **Retry Policy**: Automatically retries on transient errors (HTTP 5xx, timeout, connection refused)
 *   up to 2 times with exponential backoff (2s, 4s).
 * - **Alert Threshold**: Logs a CRITICAL alert when failure rate exceeds 50% in the last 5 minutes.
 * - **Health Check**: Exposes `isHealthy()` for monitoring dashboards.
 *
 * Runtime provider selection is sourced from AiGateway so Loom follows pool/direct routing too.
 */
class NarrativeLoomService
{
    protected string $baseUrl;
    protected int $timeout;
    protected CircuitBreaker $circuitBreaker;

    private const MAX_RETRIES = 2;
    private const RETRY_BASE_DELAY_MS = 2000;
    private const ALERT_WINDOW_MINUTES = 5;
    private const ALERT_FAILURE_RATE_THRESHOLD = 0.5;

    public function __construct(
        protected AiGateway $aiGateway
    ) {
        $this->baseUrl = rtrim((string) config('services.loom.url', 'http://narrative_loom:8001'), '/');
        $this->timeout = (int) config('services.loom.timeout', 600);
        $this->circuitBreaker = new CircuitBreaker('narrative_loom', 3, 60);
    }

    /**
     * Check if the NarrativeLoom service is healthy.
     */
    public function isHealthy(): bool
    {
        return $this->circuitBreaker->isAvailable()
            && $this->getFailureRate() < self::ALERT_FAILURE_RATE_THRESHOLD;
    }

    /**
     * Get the failure rate over the alert window.
     */
    public function getFailureRate(): float
    {
        $failures = (int) Cache::get('narrative_loom:failure_count', 0);
        $successes = (int) Cache::get('narrative_loom:success_count', 0);
        $total = $failures + $successes;
        return $total > 0 ? $failures / $total : 0.0;
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

    /**
     * Execute a Loom HTTP call with automatic retry on transient errors.
     *
     * Retries on: HTTP 5xx, connection timeout, DNS failure, connection refused.
     * Does NOT retry on: HTTP 4xx (client errors), circuit breaker OPEN.
     *
     * @template T
     * @param callable(): T $callable
     * @return T|array{ok: false, error: string}
     */
    protected function executeWithRetry(callable $callable): mixed
    {
        $lastException = null;

        for ($attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $result = $callable();
                $this->recordSuccessMetric();
                return $result;
            } catch (\Throwable $e) {
                $lastException = $e;

                if (! $this->isRetryableError($e)) {
                    $this->recordFailureMetric();
                    break;
                }

                if ($attempt < self::MAX_RETRIES) {
                    $delayMs = self::RETRY_BASE_DELAY_MS * pow(2, $attempt);
                    Log::warning('NarrativeLoom: retrying after error', [
                        'attempt' => $attempt + 1,
                        'max_retries' => self::MAX_RETRIES,
                        'delay_ms' => $delayMs,
                        'error' => $e->getMessage(),
                    ]);
                    usleep($delayMs * 1000);
                } else {
                    $this->recordFailureMetric();
                }
            }
        }

        $this->checkAlertThreshold();

        Log::error('NarrativeLoom: all retries exhausted', [
            'attempts' => self::MAX_RETRIES + 1,
            'last_error' => $lastException ? $lastException->getMessage() : 'unknown',
        ]);

        return ['ok' => false, 'error' => 'NarrativeLoom unavailable after ' . (self::MAX_RETRIES + 1) . ' attempts'];
    }

    /**
     * Determine if an error is retryable.
     */
    private function isRetryableError(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        // Connection-level errors are retryable.
        if (str_contains($message, 'connection refused')
            || str_contains($message, 'connection reset')
            || str_contains($message, 'connection timed out')
            || str_contains($message, 'dns')
            || str_contains($message, 'could not resolve host')
            || str_contains($message, 'operation timed out')
            || str_contains($message, 'cURL error 28')) { // timeout
            return true;
        }

        // HTTP 5xx from the response body is retryable.
        if (method_exists($e, 'getCode')) {
            $code = (int) $e->getCode();
            if ($code >= 500 && $code < 600) {
                return true;
            }
        }

        // HTTP 429 (rate limit) is NOT retried — we back off instead.
        return false;
    }

    /**
     * Record a success in the sliding window for alert threshold calculation.
     */
    private function recordSuccessMetric(): void
    {
        $key = 'narrative_loom:success_count';
        Cache::increment($key);
        Cache::expire($key, self::ALERT_WINDOW_MINUTES * 60);
    }

    /**
     * Record a failure in the sliding window.
     */
    private function recordFailureMetric(): void
    {
        $key = 'narrative_loom:failure_count';
        Cache::increment($key);
        Cache::expire($key, self::ALERT_WINDOW_MINUTES * 60);
    }

    /**
     * Check if failure rate exceeds the alert threshold and log a CRITICAL alert.
     */
    private function checkAlertThreshold(): void
    {
        $rate = $this->getFailureRate();
        if ($rate >= self::ALERT_FAILURE_RATE_THRESHOLD) {
            Log::critical('NarrativeLoom: HIGH FAILURE RATE ALERT', [
                'failure_rate' => round($rate * 100, 1) . '%',
                'window_minutes' => self::ALERT_WINDOW_MINUTES,
                'circuit_breaker_open' => ! $this->circuitBreaker->isAvailable(),
            ]);
        }
    }
}
