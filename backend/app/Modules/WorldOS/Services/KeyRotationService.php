<?php

namespace App\Modules\WorldOS\Services;

use App\Modules\Intelligence\Models\AiKeyPool;
use App\Modules\Intelligence\Actions\ReportKeyUsageAction;
use App\Modules\Intelligence\Actions\RotateKeyAction;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class KeyRotationService
{
    public function __construct(
        private readonly RotateKeyAction $rotateKeyAction,
        private readonly ReportKeyUsageAction $reportKeyUsageAction
    ) {}

    /**
     * Legacy WorldOS entrypoint backed by the shared key-pool rotation logic.
     */
    public function getBestKey(string $provider, string $requiredTier = 'any', ?string $modelGroup = null): ?object
    {
        $key = $this->rotateKeyAction->handle($requiredTier, $provider, $modelGroup);

        if (!$key) {
            Log::warning("WorldOS [KeyRotation]: No available key found for provider {$provider}");
            return null;
        }

        try {
            $decrypted = (string) ($key->getRawOriginal('key_encrypted') ?? $key->key_encrypted ?? '');
            if ($this->looksLikeEncryptedPayload($decrypted)) {
                $decrypted = Crypt::decryptString($decrypted);
            }

            $this->reportKeyUsageAction->handle($key);

            return (object) [
                'id' => $key->id,
                'value' => $decrypted,
                'provider' => $key->provider,
                'label' => $key->label,
                'tier' => $key->tier,
                'level' => $key->level,
                'model_group' => $key->model_group,
                'metadata' => $key->metadata ?? [],
                'base_url' => $key->metadata['url'] ?? null,
                'model' => $key->metadata['model'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error("WorldOS [KeyRotation]: Failed to decrypt key ID {$key->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Report rate limit using the same cooldown handling as the new pool flow.
     */
    public function reportRateLimit(int $keyId, int $cooldownMinutes = 60): void
    {
        $key = AiKeyPool::find($keyId);
        if (!$key) {
            return;
        }

        $this->reportKeyUsageAction->handle($key, 429, [
            'requested_cooldown_minutes' => $cooldownMinutes,
        ]);

        $key->forceFill([
            'status' => 'cooldown',
            'cooldown_until' => now()->addMinutes($cooldownMinutes),
        ])->save();

        Log::info("WorldOS [KeyRotation]: Key #{$keyId} moved to cooldown.");
    }

    /**
     * Register a key in the current pool schema while remaining compatible with legacy callers.
     */
    public function registerKey(
        string $provider,
        string $plainKey,
        bool $isFree = true,
        ?string $label = null,
        ?string $tier = null,
        ?int $level = null,
        ?string $modelGroup = null,
        array $metadata = []
    ): AiKeyPool {
        return AiKeyPool::create([
            'provider' => $provider,
            'label' => $label,
            'key_encrypted' => Crypt::encryptString($plainKey),
            'model_group' => $modelGroup,
            'tier' => $tier ?? ($isFree ? 'free' : 'premium'),
            'level' => $level ?? 1,
            'is_free' => $isFree,
            'usage_count' => 0,
            'status' => 'active',
            'metadata' => $metadata,
        ]);
    }

    private function looksLikeEncryptedPayload(string $value): bool
    {
        $decoded = base64_decode($value, true);

        if (!is_string($decoded) || $decoded === '') {
            return false;
        }

        $payload = json_decode($decoded, true);

        return is_array($payload)
            && isset($payload['iv'], $payload['value'], $payload['mac']);
    }
}
