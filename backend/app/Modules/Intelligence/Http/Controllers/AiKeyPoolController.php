<?php

namespace App\Modules\Intelligence\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Intelligence\Models\AiKeyPool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class AiKeyPoolController extends Controller
{
    public function index()
    {
        return response()->json(
            AiKeyPool::query()
                ->orderByDesc('id')
                ->get()
                ->map(fn (AiKeyPool $key) => $this->serializeKey($key))
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|string',
            'label' => 'required|string',
            'key' => 'required|string',
            'tier' => 'required|in:free,premium',
            'status' => 'nullable|in:active,inactive,cooldown',
            'level' => 'integer|min:1',
            'model_group' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $key = new AiKeyPool($validated);
        $key->is_free = ($request->tier === 'free');
        $key->key_encrypted = Crypt::encryptString($request->key);
        $key->status = $validated['status'] ?? 'active';
        $key->cooldown_until = $key->status === 'cooldown' ? $key->cooldown_until : null;
        $key->save();

        return response()->json($this->serializeKey($key), 201);
    }

    public function update(Request $request, AiKeyPool $ai_key_pool)
    {
        $validated = $request->validate([
            'provider' => 'string',
            'label' => 'string',
            'status' => 'in:active,inactive,cooldown',
            'tier' => 'in:free,premium',
            'level' => 'integer|min:1',
            'model_group' => 'nullable|string',
            'metadata' => 'array',
        ]);

        if ($request->has('key')) {
            $validated['key_encrypted'] = Crypt::encryptString($request->key);
        }

        if (array_key_exists('tier', $validated)) {
            $validated['is_free'] = $validated['tier'] === 'free';
        }

        if (array_key_exists('status', $validated) && $validated['status'] !== 'cooldown') {
            $validated['cooldown_until'] = null;
        }

        $ai_key_pool->update($validated);
        $ai_key_pool->refresh();

        return response()->json($this->serializeKey($ai_key_pool));
    }

    public function destroy(AiKeyPool $ai_key_pool)
    {
        $ai_key_pool->delete();
        return response()->json(null, 204);
    }

    private function serializeKey(AiKeyPool $key): array
    {
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
            'key_preview' => $this->buildPreview($key),
        ];
    }

    private function buildPreview(AiKeyPool $key): string
    {
        $value = (string) ($key->getRawOriginal('key_encrypted') ?? '');

        try {
            if ($value !== '' && $this->looksLikeEncryptedPayload($value)) {
                $value = Crypt::decryptString($value);
            }
        } catch (\Throwable) {
            // Fall back to the raw stored value when decrypting legacy rows fails.
        }

        $suffix = strlen($value) >= 4 ? substr($value, -4) : $value;

        return '********' . $suffix;
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
