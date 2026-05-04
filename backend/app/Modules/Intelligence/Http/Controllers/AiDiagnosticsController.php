<?php

namespace App\Modules\Intelligence\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Intelligence\Services\AI\AiConfigManager;
use App\Modules\Intelligence\Services\AI\AiGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiDiagnosticsController extends Controller
{
    public function __construct(
        protected AiGateway $aiGateway,
        protected AiConfigManager $configManager,
    ) {}

    public function run(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'driver' => 'nullable|string|max:64',
            'prompt' => 'nullable|string|max:2000',
        ]);

        $driver = (string) ($validated['driver'] ?? $this->configManager->get('default', config('ai.default', 'local')));
        $prompt = trim((string) ($validated['prompt'] ?? 'Ping from WorldOS diagnostics. Describe your readiness in one sentence.'));
        $startedAt = microtime(true);

        try {
            $response = $this->aiGateway->driver($driver, 'diagnostic')->chat([
                [
                    'role' => 'system',
                    'content' => 'You are a WorldOS diagnostics probe. Reply briefly with runtime readiness and any blocking issue.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ]);

            return response()->json([
                'status' => 'success',
                'driver' => $driver,
                'prompt' => $prompt,
                'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'response' => $response,
                'checked_at' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'driver' => $driver,
                'prompt' => $prompt,
                'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'error' => $exception->getMessage(),
                'checked_at' => now()->toIso8601String(),
            ], 500);
        }
    }
}
