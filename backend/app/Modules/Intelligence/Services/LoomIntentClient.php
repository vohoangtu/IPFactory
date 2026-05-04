<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\Intelligence\Domain\Policy\IntentResponse;
use App\Modules\Intelligence\Domain\Policy\UniverseContext;
use App\Modules\Intelligence\Entities\ActorEntity;
use App\Modules\Intelligence\Services\AI\Concerns\LogsAiCalls;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP client that calls narrative-loom /actor-intent.
 * Hard timeout - MUST fallback to DecisionEngine on failure.
 */
class LoomIntentClient
{
    use LogsAiCalls;

    private const FEATURE = 'decision';

    private string $baseUrl;

    public function __construct(
        private readonly \App\Modules\Intelligence\Services\AI\AiGateway $aiGateway
    ) {
        $this->baseUrl = rtrim(
            (string) config('services.narrative_loom.url', config('services.loom.url', env('NARRATIVE_LOOM_URL', 'http://narrative_loom:8001'))),
            '/'
        );
    }

    /**
     * Request LLM intent for an actor.
     * Returns null on timeout, error, low confidence, or pool-exhausted.
     */
    public function requestIntent(ActorEntity $actor, UniverseContext $ctx): ?IntentResponse
    {
        $traits = $this->buildTraitMap($actor);

        try {
            $runtime = $this->aiGateway->runtimeProfileForFeature(self::FEATURE);
        } catch (\App\Modules\Intelligence\Exceptions\AiPoolExhaustedException $e) {
            Log::debug('[LoomIntentClient] Pool exhausted for decision feature: ' . $e->getMessage());
            return null;
        }

        $payload = [
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'archetype' => $actor->archetype,
            'traits' => $traits,
            'universe_context' => [
                'entropy' => $ctx->entropy,
                'stability_index' => $ctx->stabilityIndex,
                'myth_intensity' => $ctx->mythIntensity,
                'tick' => $ctx->tick,
            ],
            'recent_biography' => $this->extractRecentBio($actor),
            'available_actions' => [
                'revolt', 'form_contract', 'migrate',
                'trade', 'suppress_revolt', 'propagate_myth',
            ],
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
        $keyEntry = $runtime['key_entry'] ?? null;

        $startTime = microtime(true);
        try {
            $response = Http::timeout(120)->post("{$this->baseUrl}/actor-intent", $payload);
            $latency = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->failed()) {
                Log::debug("[LoomIntentClient] HTTP {$response->status()} for actor {$actor->id}: " . $response->body());
                $this->logToDatabase($payload, $response->body(), $latency, 'error', "HTTP {$response->status()}", $payload['provider']);
                if ($keyEntry) {
                    app(\App\Modules\Intelligence\Actions\ReportKeyUsageAction::class)->handle(
                        $keyEntry,
                        $this->resolveErrorCodeFromHttpFailure($response->status(), $response->body())
                    );
                }
                return null;
            }

            $data = $response->json();
            Log::debug("[LoomIntentClient] Response for actor {$actor->id}: " . json_encode($data));
            $intent = IntentResponse::fromArray($data);

            $this->logToDatabase($payload, $data, $latency, 'success', null, $payload['provider']);

            if ($keyEntry) {
                app(\App\Modules\Intelligence\Actions\ReportKeyUsageAction::class)->handle($keyEntry);
            }

            return $intent->isReliable() ? $intent : null;
        } catch (\Throwable $e) {
            $latency = (int) ((microtime(true) - $startTime) * 1000);
            Log::debug("[LoomIntentClient] Timeout/error for actor {$actor->id}: {$e->getMessage()}");

            $this->logToDatabase($payload, null, $latency, 'error', $e->getMessage(), $payload['provider']);

            if ($keyEntry) {
                app(\App\Modules\Intelligence\Actions\ReportKeyUsageAction::class)->handle(
                    $keyEntry,
                    $this->resolveErrorCodeFromThrowable($e)
                );
            }

            return null;
        }
    }

    private function logToDatabase(
        array $input,
        mixed $output,
        int $latency,
        string $status,
        ?string $error = null,
        string $driver = 'local'
    ): void {
        $model = is_string($input['model_name'] ?? null) && trim((string) $input['model_name']) !== ''
            ? trim((string) $input['model_name'])
            : (is_string($input['model'] ?? null) && trim((string) $input['model']) !== '' ? trim((string) $input['model']) : null);

        $this->recordAiLog([
            'feature' => self::FEATURE,
            'driver' => $driver,
            'model' => $model,
            'input' => $input,
            'output' => is_string($output) ? ['text' => $output] : $output,
            'latency_ms' => $latency,
            'status' => $status,
            'error_message' => $error,
        ]);
    }

    private function buildTraitMap(ActorEntity $actor): array
    {
        $dimensions = ActorEntity::TRAIT_DIMENSIONS;
        $map = [];
        foreach ($dimensions as $i => $name) {
            $map[$name] = round((float) ($actor->traits[$i] ?? 0.5), 3);
        }
        return $map;
    }

    private function extractRecentBio(ActorEntity $actor): string
    {
        if (!$actor->biography) {
            return '';
        }

        $lines = array_filter(explode("\n", $actor->biography));
        $recent = array_slice($lines, -5);
        return implode("\n", $recent);
    }
}
