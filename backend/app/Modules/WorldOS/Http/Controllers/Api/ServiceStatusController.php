<?php

declare(strict_types=1);

namespace App\Modules\WorldOS\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Reports health status of all internal services.
 */
class ServiceStatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [];
        $overallOk = true;

        // Run all checks (lightweight, 3s timeout each)
        $checks['database'] = $this->checkDatabase();
        $checks['redis'] = $this->checkRedis();
        $checks['engine'] = $this->checkEngine();
        $checks['narrative_loom'] = $this->checkNarrativeLoom();
        $checks['social_engine'] = $this->checkSocialEngine();

        foreach ($checks as $check) {
            if ($check['status'] !== 'ok') {
                $overallOk = false;
            }
        }

        return response()->json([
            'overall' => $overallOk ? 'healthy' : 'degraded',
            'services' => $checks,
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $ms = round((microtime(true) - $start) * 1000, 1);

            return ['status' => 'ok', 'latency_ms' => $ms];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            $start = microtime(true);
            $key = 'service_status:ping:' . time();
            Cache::put($key, 'ok', 5);
            $value = Cache::get($key);
            Cache::forget($key);
            $ms = round((microtime(true) - $start) * 1000, 1);

            return $value === 'ok'
                ? ['status' => 'ok', 'latency_ms' => $ms]
                : ['status' => 'error', 'error' => 'Cache read mismatch'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    private function checkEngine(): array
    {
        try {
            $host = config('services.simulation_engine.host', 'engine');
            $port = 50052;
            $start = microtime(true);
            $response = Http::timeout(3)->get("http://{$host}:{$port}/health");
            $ms = round((microtime(true) - $start) * 1000, 1);

            return $response->successful()
                ? ['status' => 'ok', 'latency_ms' => $ms]
                : ['status' => 'error', 'http_status' => $response->status()];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'error' => 'unreachable'];
        }
    }

    private function checkNarrativeLoom(): array
    {
        try {
            $url = rtrim((string) config('services.loom.url', 'http://narrative_loom:8001'), '/');
            $start = microtime(true);
            $response = Http::timeout(3)->get("{$url}/health");
            $ms = round((microtime(true) - $start) * 1000, 1);

            // Also report circuit breaker state
            $cbState = Cache::get('circuit_breaker:narrative_loom:state', 'closed');

            return $response->successful()
                ? ['status' => 'ok', 'latency_ms' => $ms, 'circuit_breaker' => $cbState]
                : ['status' => 'error', 'http_status' => $response->status(), 'circuit_breaker' => $cbState];
        } catch (\Throwable $e) {
            $cbState = Cache::get('circuit_breaker:narrative_loom:state', 'closed');

            return ['status' => 'error', 'error' => 'unreachable', 'circuit_breaker' => $cbState];
        }
    }

    private function checkSocialEngine(): array
    {
        try {
            $url = rtrim((string) config('services.social_engine.url', 'http://social_engine:5001/api/v1'), '/');
            // Remove /api/v1 suffix to hit /health
            $baseUrl = preg_replace('#/api/v1$#', '', $url);
            $start = microtime(true);
            $response = Http::timeout(3)->get("{$baseUrl}/health");
            $ms = round((microtime(true) - $start) * 1000, 1);

            return $response->successful()
                ? ['status' => 'ok', 'latency_ms' => $ms]
                : ['status' => 'error', 'http_status' => $response->status()];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'error' => 'unreachable'];
        }
    }
}
