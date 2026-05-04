<?php

namespace App\Modules\Simulation\Core\StateCache;

use App\Modules\Simulation\Core\Contracts\StateCacheInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

final class RedisStateCache implements StateCacheInterface
{
    public function __construct(
        private readonly string $keyPrefix,
        private readonly int $ttlSeconds,
    ) {}

    public function get(int $universeId): ?array
    {
        try {
            $key = $this->keyPrefix . 'universe:' . $universeId . ':state';
            $raw = Redis::get($key);
            if ($raw === null || $raw === '') {
                return null;
            }
            $data = json_decode($raw, true);
            if (! is_array($data) || ! isset($data['state_vector'], $data['tick'])) {
                return null;
            }

            return [
                'state_vector' => $data['state_vector'],
                'tick' => (int) $data['tick'],
            ];
        } catch (\Throwable $e) {
            Log::debug('StateCache get failed: ' . $e->getMessage(), ['universe_id' => $universeId]);
            return null;
        }
    }

    public function set(int $universeId, array $stateVector, int $tick): void
    {
        try {
            $key = $this->keyPrefix . 'universe:' . $universeId . ':state';
            $payload = json_encode([
                'state_vector' => $stateVector,
                'tick' => $tick,
            ]);
            Redis::setex($key, $this->ttlSeconds, $payload);
        } catch (\Throwable $e) {
            Log::warning('StateCache set failed: ' . $e->getMessage(), ['universe_id' => $universeId]);
        }
    }
}
