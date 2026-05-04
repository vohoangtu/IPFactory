<?php
declare(strict_types=1);

namespace App\Modules\Simulation\Core\Services;

use App\Modules\Simulation\Core\Domain\EngineExecutionRecord;
use App\Modules\Simulation\Core\Domain\SimulationTickResult;
use Illuminate\Support\Facades\Cache;

/**
 * Service to aggregate and persist tick performance metrics.
 * Uses Cache/Redis for lightweight, short-term telemetry storage.
 */
class TickMetricsService
{
    private const CACHE_PREFIX = 'simulation:metrics:';
    private const MAX_RECORDS = 50; // Keep last 50 ticks for health history

    /**
     * Persist metrics from a single tick result.
     */
    public function recordTick(int $universeId, SimulationTickResult $result, int $tick): void
    {
        $metrics = array_map(fn(EngineExecutionRecord $r) => $r->toArray(), $result->engineMetrics);

        $payload = [
            'tick' => $tick,
            'timestamp' => microtime(true),
            'engines' => $metrics,
        ];

        $key = self::CACHE_PREFIX . $universeId;
        
        // Push to a list in cache (simulated via array merge for simplicity if not using Redis Lpush)
        $history = Cache::get($key, []);
        array_unshift($history, $payload);
        
        // Trim to MAX_RECORDS
        if (count($history) > self::MAX_RECORDS) {
            $history = array_slice($history, 0, self::MAX_RECORDS);
        }

        Cache::put($key, $history, now()->addHours(1));
    }

    /**
     * Retrieve health history for a universe.
     */
    public function getHistory(int $universeId): array
    {
        return Cache::get(self::CACHE_PREFIX . $universeId, []);
    }

    /**
     * Calculate aggregate health score based on recent performance.
     */
    public function getAggregateHealth(int $universeId): array
    {
        $history = $this->getHistory($universeId);
        if (empty($history)) {
            return ['score' => 100, 'avg_ms' => 0, 'skip_rate' => 0];
        }

        $totalMs = 0;
        $totalEngines = 0;
        $skippedEngines = 0;
        $ticksCount = count($history);

        foreach ($history as $tickData) {
            foreach ($tickData['engines'] as $engine) {
                $totalMs += $engine['elapsed_ms'];
                $totalEngines++;
                if ($engine['was_skipped']) {
                    $skippedEngines++;
                }
            }
        }

        $avgMsPerTick = $totalMs / $ticksCount;
        $skipRate = ($totalEngines > 0) ? ($skippedEngines / $totalEngines) * 100 : 0;
        
        // Heuristic: start with 100, subtract points for skipping and high latency
        $score = 100 - ($skipRate * 2) - min(40, $avgMsPerTick / 20);

        return [
            'score' => round(max(0, $score), 2),
            'avg_ms_per_tick' => round($avgMsPerTick, 2),
            'skip_rate' => round($skipRate, 2),
            'history_count' => $ticksCount
        ];
    }
}
