<?php

namespace App\Modules\Simulation\Services\Core;

use Illuminate\Support\Facades\Redis;

/**
 * Observer: publish universe events to Redis Stream for realtime dashboard.
 * Consumer: readStream() for frontend long-poll or SSE. Stream key: universe:events or universe:events:{id}.
 */
class ObserverService
{
    protected string $streamPrefix = 'universe:events';

    public function publishSnapshot(int $universeId, ?int $multiverseId, int $tick, array $payload): void
    {
        $key = $this->streamKey($multiverseId);
        $payload['universe_id'] = $universeId;
        $payload['tick'] = $tick;
        $payload['event'] = 'snapshot';
        $payload['at'] = now()->toIso8601String();
        $this->addToStream($key, $payload);
    }

    public function publishEvent(int $universeId, ?int $multiverseId, string $eventType, array $payload): void
    {
        $key = $this->streamKey($multiverseId);
        $payload['universe_id'] = $universeId;
        $payload['event'] = $eventType;
        $payload['at'] = now()->toIso8601String();
        $this->addToStream($key, $payload);
    }

    /**
     * Read events from stream (for frontend / long-poll). Returns entries after $lastId, max $count.
     *
     * @return array<int, array{id: string, data: array<string, string>}>
     */
    public function readStream(?int $multiverseId, string $lastId = '0', int $count = 50): array
    {
        $key = $this->streamKey($multiverseId);
        try {
            $result = Redis::xRead([$key => $lastId], $count, 0);
            if (!is_array($result) || !isset($result[$key])) {
                return [];
            }
            $entries = [];
            foreach ($result[$key] as $id => $data) {
                $entries[] = ['id' => $id, 'data' => $data];
            }
            return $entries;
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    /**
     * Blocking read: wait up to $blockMs for new entries. Use for SSE to avoid polling.
     * Returns [entries, lastId]. lastId is updated to the last entry id for next call.
     *
     * @return array{0: array<int, array{id: string, data: array<string, string>}>, 1: string}
     */
    public function readStreamBlocking(?int $multiverseId, string &$lastId, int $count = 50, int $blockMs = 5000): array
    {
        $key = $this->streamKey($multiverseId);
        try {
            $result = Redis::xRead([$key => $lastId], $count, $blockMs);
            if (!is_array($result) || !isset($result[$key])) {
                return [[], $lastId];
            }
            $entries = [];
            foreach ($result[$key] as $id => $data) {
                $entries[] = ['id' => $id, 'data' => $data];
                $lastId = $id;
            }
            return [$entries, $lastId];
        } catch (\Throwable $e) {
            report($e);
            return [[], $lastId];
        }
    }

    public function streamKey(?int $multiverseId): string
    {
        return $multiverseId !== null
            ? "{$this->streamPrefix}:{$multiverseId}"
            : $this->streamPrefix;
    }

    protected function addToStream(string $key, array $payload): void
    {
        try {
            $flat = [];
            foreach ($payload as $k => $v) {
                $flat[$k] = is_scalar($v) ? (string) $v : json_encode($v);
            }
            
            // Predis compatibility: Laravel's Redis::xAdd with Predis driver 
            // might have issues with positional arguments for MAXLEN.
            // Using a more explicit way or ensuring standard positional args.
            if (config('database.redis.client') === 'predis') {
                Redis::connection()->executeRaw(['XADD', $key, 'MAXLEN', '~', '10000', '*', ...$this->flattenForRaw($flat)]);
            } else {
                Redis::xAdd($key, '*', $flat, 10000);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function flattenForRaw(array $data): array
    {
        $flattened = [];
        foreach ($data as $key => $value) {
            $flattened[] = $key;
            $flattened[] = $value;
        }
        return $flattened;
    }
}
