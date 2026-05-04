<?php

namespace App\Modules\Narrative\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LoomTaskStatusController extends Controller
{
    /**
     * Proxy task status from the NarrativeLoom service so the frontend can
     * poll through the Laravel API boundary when WebSocket connectivity drops.
     */
    public function show(string $taskId): JsonResponse
    {
        $loomUrl = rtrim((string) config('services.loom.url', 'http://narrative_loom:8001'), '/');

        try {
            $response = Http::timeout(5)->get("{$loomUrl}/tasks/{$taskId}/status");

            if ($response->failed()) {
                return response()->json([
                    'status' => 'error',
                    'task_id' => $taskId,
                    'message' => 'NarrativeLoom task status is unavailable.',
                ], $response->status());
            }

            return response()->json($response->json());
        } catch (\Throwable $e) {
            Log::warning('Loom task status check failed', [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'task_id' => $taskId,
                'message' => 'Failed to reach NarrativeLoom task status endpoint.',
            ], 503);
        }
    }
}
