<?php

namespace App\Modules\Narrative\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LoomStatusController extends Controller
{
    /**
     * GET /api/ip-factory/loom-status
     * Fetch status and configuration from NarrativeLoom service.
     */
    public function status(): JsonResponse
    {
        $loomUrl = config('services.loom.url', 'http://narrative_loom:8001');

        try {
            // 1. Check Health/Root
            $healthResponse = Http::timeout(2)->get($loomUrl . '/');
            $isHealthy = $healthResponse->successful();

            if (!$isHealthy) {
                 return response()->json([
                    'status' => 'offline',
                    'message' => 'NarrativeLoom service is unreachable.',
                    'agents' => []
                ]);
            }

            // 2. Fetch Config
            $configResponse = Http::timeout(3)->get($loomUrl . '/config');
            
            if ($configResponse->failed()) {
                return response()->json([
                    'status' => 'degraded',
                    'message' => 'Service is up but config is unreachable.',
                    'agents' => []
                ]);
            }

            $data = $configResponse->json();
            
            return response()->json([
                'status' => 'online',
                'agents' => $data['agents'] ?? [],
                'providers' => $data['providers'] ?? [],
                'version' => $healthResponse->json('version', 'unknown')
            ]);

        } catch (\Exception $e) {
            Log::error("Loom Status Check Failed: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to connect to narrative_loom: ' . $e->getMessage(),
                'agents' => []
            ], 503);
        }
    }
}
