<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Services\AI;

use App\Modules\Intelligence\Models\AiKeyPool;
use App\Modules\Intelligence\Models\AiProviderModel;
use App\Modules\Intelligence\Actions\RotateKeyAction;
use App\Modules\Intelligence\Contracts\LlmDriverInterface;
use App\Modules\Intelligence\Exceptions\AiPoolExhaustedException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class AiProviderRouter
{
    public function __construct(
        protected AiConfigManager $configManager,
        protected AiResponseNormalizer $normalizer
    ) {
    }

    /**
     * Resolve the runtime connection profile that should be used for a feature.
     *
     * @return array{
     *   provider:string,
     *   model:?string,
     *   base_url:?string,
     *   api_key:?string,
     *   tier:string,
     *   from_pool:bool,
     *   default_options:array<string,mixed>,
     *   key_entry:?AiKeyPool
     * }
     */
    /**
     * Pool-only resolution. Always resolves through ai_key_pool.
     *
     * @param  callable  $usesPoolFn  kept for signature compatibility; ignored
     * @throws AiPoolExhaustedException when no usable pool key matches
     */
    public function resolveRuntimeProfile(?string $name, string $feature, array $featureProfile, string $forcedTier, callable $usesPoolFn): array
    {
        $requestedName = $name
            ?: ($featureProfile['driver'] ?? null)
            ?: $this->configManager->get('default', config('ai.default', null));

        $requiredTier = $forcedTier !== 'any'
            ? $forcedTier
            : ($featureProfile['tier'] ?? 'any');
        $providerFilter = $requestedName !== 'pool' && $requestedName !== null
            ? (string) $requestedName
            : ($featureProfile['provider'] ?? null);
        $modelGroup = $featureProfile['model_group'] ?? null;
        $exactModel = $featureProfile['model'] ?? null;
        $driverOverrides = $this->normalizer->extractDriverOverrides($featureProfile);
        $defaultOptions = $this->normalizer->extractDefaultOptions($featureProfile);

        $poolRuntime = $this->resolveUsablePoolKey(
            $requiredTier,
            $providerFilter,
            $modelGroup,
            $exactModel
        );

        if (!$poolRuntime) {
            throw AiPoolExhaustedException::forFeature(
                $feature,
                $providerFilter,
                $requiredTier,
                $modelGroup,
                $exactModel,
            );
        }

        $key = $poolRuntime['key'];

        $resolvedModel = $driverOverrides['model'] ?? ($key->metadata['model'] ?? null);
        if (empty($resolvedModel)) {
            $resolvedModel = $this->pickDefaultModelFromRegistry($key->provider, $key->tier);
        }
        if (empty($resolvedModel)) {
            $resolvedModel = $this->normalizer->defaultModelForProvider($key->provider);
        }

        return [
            'provider' => $key->provider,
            'model' => $resolvedModel,
            'base_url' => $driverOverrides['url'] ?? ($key->metadata['url'] ?? $this->normalizer->defaultUrlForProvider($key->provider)),
            'api_key' => $poolRuntime['api_key'],
            'tier' => $key->tier,
            'from_pool' => true,
            'default_options' => $defaultOptions,
            'key_entry' => $key,
        ];
    }

    public function createDriverFromPoolRuntime(array $runtime, array $overrides = []): LlmDriverInterface
    {
        /** @var AiKeyPool $key */
        $key = $runtime['key_entry'];
        $apiKey = (string) ($runtime['api_key'] ?? '');
        $baseUrl = $overrides['url'] ?? ($runtime['base_url'] ?? ($key->metadata['url'] ?? null));
        $model = $overrides['model'] ?? ($runtime['model'] ?? ($key->metadata['model'] ?? null));

        return match ($key->provider) {
            'zai' => new Drivers\ZaiDriver(
                $baseUrl ?? 'https://api.z.ai/api/paas/v4/chat/completions',
                $apiKey,
                $model ?? 'GLM-4.5-Flash'
            ),
            'openai' => new Drivers\OpenAiDriver(
                $baseUrl ?? 'https://api.openai.com/v1/chat/completions',
                $apiKey,
                $model ?? 'gpt-4o'
            ),
            'gemini' => new Drivers\OpenAiDriver(
                $baseUrl ?? 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions',
                $apiKey,
                $model ?? 'gemini-1.5-flash'
            ),
            'openrouter' => new Drivers\OpenRouterDriver(
                $baseUrl ?? 'https://openrouter.ai/api/v1/chat/completions',
                $apiKey,
                $model ?? ''
            ),
            'local' => new Drivers\LocalDriver(
                $baseUrl ?? 'http://host.docker.internal:11434/v1/chat/completions',
                $model ?? 'qwen2.5:7b'
            ),
            default => new Drivers\OpenAiDriver(
                $baseUrl ?? '',
                $apiKey,
                $model ?? ''
            ),
        };
    }

    public function resolveUsablePoolKey(
        string $requiredTier = 'any',
        ?string $provider = null,
        ?string $modelGroup = null,
        ?string $model = null
    ): ?array {
        $attemptedKeyIds = [];
        $tiersToTry = $requiredTier !== 'any' ? [$requiredTier, 'any'] : ['any'];

        foreach ($tiersToTry as $tier) {
            while (true) {
                /** @var RotateKeyAction $rotator */
                $rotator = app(RotateKeyAction::class);
                $key = $rotator->handle($tier, $provider, $modelGroup, $model);

                if (! $key || in_array($key->id, $attemptedKeyIds, true)) {
                    break;
                }

                try {
                    return [
                        'key' => $key,
                        'api_key' => $this->decryptPoolKey($key),
                    ];
                } catch (\RuntimeException) {
                    $attemptedKeyIds[] = $key->id;
                }
            }
        }

        return null;
    }

    private function pickDefaultModelFromRegistry(string $provider, string $tier): ?string
    {
        try {
            $query = AiProviderModel::query()
                ->where('provider', $provider)
                ->where('is_active', true)
                ->orderByRaw("CASE metadata->>'tier' WHEN 'free' THEN 0 WHEN 'mini' THEN 1 WHEN 'pro' THEN 2 ELSE 3 END")
                ->orderBy('id');

            $model = $query->first();
            return $model?->model_name;
        } catch (\Throwable $e) {
            Log::warning('Failed to pick default model from registry', ['provider' => $provider, 'tier' => $tier, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function decryptPoolKey(AiKeyPool $key): string
    {
        $apiKey = (string) ($key->getRawOriginal('key_encrypted') ?? $key->key_encrypted ?? '');

        if ($apiKey === '') {
            return '';
        }

        try {
            if ($this->looksLikeEncryptedPayload($apiKey)) {
                $apiKey = Crypt::decryptString($apiKey);
            }
        } catch (\Throwable $e) {
            Log::error("Failed to decrypt AI Key ID: {$key->id}: {$e->getMessage()}");
            $this->markPoolKeyAsBroken($key, $e);
            throw new \RuntimeException("AI pool key #{$key->id} could not be decrypted.", 0, $e);
        }

        return $apiKey;
    }

    public function markPoolKeyAsBroken(AiKeyPool $key, \Throwable $error): void
    {
        try {
            $metadata = is_array($key->metadata) ? $key->metadata : [];
            $metadata['last_error'] = 'decrypt_failed';
            $metadata['last_error_message'] = $error->getMessage();
            $metadata['last_error_at'] = now()->toIso8601String();

            $key->status = 'inactive';
            $key->cooldown_until = null;
            $key->metadata = $metadata;
            $key->save();
        } catch (\Throwable $persistError) {
            Log::warning("Failed to quarantine broken AI Key ID: {$key->id}: {$persistError->getMessage()}");
        }
    }

    public function looksLikeEncryptedPayload(string $value): bool
    {
        $decoded = base64_decode($value, true);

        if (! is_string($decoded) || $decoded === '') {
            return false;
        }

        $payload = json_decode($decoded, true);

        return is_array($payload)
            && isset($payload['iv'], $payload['value'], $payload['mac']);
    }
}
