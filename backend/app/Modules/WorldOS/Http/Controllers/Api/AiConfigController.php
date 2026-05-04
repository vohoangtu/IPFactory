<?php

namespace App\Modules\WorldOS\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Intelligence\Models\AiKeyPool;
use App\Modules\Intelligence\Models\AiSetting;
use App\Modules\WorldOS\Services\KeyRotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class AiConfigController extends Controller
{
    public function __construct(
        private KeyRotationService $rotationService
    ) {}

    /**
     * Legacy WorldOS endpoint that now exposes the richer key-pool schema.
     */
    public function listKeys(): JsonResponse
    {
        $keys = AiKeyPool::all()->map(function (AiKeyPool $key) {
            $previewSource = (string) ($key->getRawOriginal('key_encrypted') ?? $key->key_encrypted ?? '');
            try {
                if ($previewSource !== '' && $this->looksLikeEncryptedPayload($previewSource)) {
                    $previewSource = Crypt::decryptString($previewSource);
                }
            } catch (\Throwable) {
                // Keep encrypted preview fallback for malformed legacy rows.
            }

            $previewSuffix = strlen($previewSource) >= 4 ? substr($previewSource, -4) : $previewSource;

            return [
                'id' => $key->id,
                'provider' => $key->provider,
                'label' => $key->label,
                'model_group' => $key->model_group,
                'tier' => $key->tier,
                'level' => $key->level,
                'is_free' => $key->is_free,
                'usage_count' => $key->usage_count,
                'status' => $key->status,
                'last_used_at' => $key->last_used_at?->toIso8601String(),
                'cooldown_until' => $key->cooldown_until?->toIso8601String(),
                'metadata' => $key->metadata ?? [],
                'key_preview' => '********' . $previewSuffix,
            ];
        });

        return response()->json(['data' => $keys]);
    }

    /**
     * Accept both legacy payloads (api_key/is_free) and the new pool schema.
     */
    public function storeKey(Request $request): JsonResponse
    {
        $request->validate([
            'provider' => 'required|string',
            'api_key' => 'required_without:key|string',
            'key' => 'required_without:api_key|string',
            'label' => 'nullable|string',
            'is_free' => 'nullable|boolean',
            'tier' => 'nullable|in:free,premium',
            'level' => 'nullable|integer|min:1',
            'model_group' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $plainKey = (string) ($request->input('api_key') ?? $request->input('key'));
        $isFree = $request->has('is_free')
            ? $request->boolean('is_free')
            : (($request->input('tier') ?? 'free') === 'free');

        $key = $this->rotationService->registerKey(
            $request->input('provider'),
            $plainKey,
            $isFree,
            $request->input('label'),
            $request->input('tier'),
            $request->input('level'),
            $request->input('model_group'),
            $request->input('metadata', [])
        );

        return response()->json([
            'message' => 'Key registered successfully.',
            'data' => [
                'id' => $key->id,
                'provider' => $key->provider,
                'label' => $key->label,
                'model_group' => $key->model_group,
                'tier' => $key->tier,
                'level' => $key->level,
                'is_free' => $key->is_free,
                'status' => $key->status,
                'metadata' => $key->metadata ?? [],
            ],
        ], 201);
    }

    /**
     * Remove a key from the pool.
     */
    public function destroyKey(int $id): JsonResponse
    {
        $key = AiKeyPool::findOrFail($id);
        $key->delete();

        return response()->json(['message' => 'Key removed.']);
    }

    /**
     * Get general AI settings.
     */
    public function getSettings(): JsonResponse
    {
        $settings = AiSetting::whereIn('key', [
            'narrative.style',
            'agent.routing',
            'sim.tick_rate',
        ])->get()->pluck('value', 'key');

        return response()->json(['data' => $settings]);
    }

    /**
     * Update an AI setting.
     */
    public function updateSetting(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'required',
        ]);

        AiSetting::updateOrCreate(
            ['key' => $request->key],
            ['value' => is_array($request->value) ? json_encode($request->value) : $request->value]
        );

        return response()->json(['message' => 'Setting updated.']);
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
