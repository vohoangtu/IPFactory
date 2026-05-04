<?php

namespace App\Modules\Simulation\Services\Core;

use App\Contracts\CausalityGraphServiceInterface;
use Illuminate\Support\Facades\Redis;

/**
 * doc §4: store causality chain per universe in Redis (event_id → cause_event_id).
 */
final class RedisCausalityGraphService implements CausalityGraphServiceInterface
{
    private const KEY_LAST = 'worldos:causality:last:%s';
    private const KEY_CHAIN = 'worldos:causality:chain:%s';
    private const CHAIN_TTL = 86400 * 30; // 30 days

    private const KEY_LINKS = 'worldos:causality:semantic_links';

    public function recordEvent(int $universeId, string $eventId, string $type, int $tick): void
    {
        $keyLast = sprintf(self::KEY_LAST, $universeId);
        $keyChain = sprintf(self::KEY_CHAIN, $universeId);
        $causeEventId = Redis::get($keyLast);
        $link = json_encode([
            'event_id' => $eventId,
            'type' => $type,
            'tick' => $tick,
            'cause_event_id' => $causeEventId ?: null,
        ]);
        Redis::rPush($keyChain, $link);
        Redis::expire($keyChain, self::CHAIN_TTL);
        Redis::setex($keyLast, self::CHAIN_TTL, $eventId);
    }

    public function recordRelation(string $src, string $relation, string $target, int $tick, array $metadata = []): void
    {
        $linkObj = [
            'src' => $src,
            'rel' => $relation,
            'tgt' => $target,
            'tick' => $tick,
            'meta' => $metadata,
            'timestamp' => now()->timestamp,
        ];
        $link = json_encode($linkObj);

        Redis::lPush(self::KEY_LINKS, $link);
        Redis::lTrim(self::KEY_LINKS, 0, 99); // Global last 100

        // If universe_id is present, store in universe-specific list
        if (isset($metadata['universe_id'])) {
            $key = sprintf('worldos:causality:links:%d', (int) $metadata['universe_id']);
            Redis::lPush($key, $link);
            Redis::lTrim($key, 0, 99); // Universe-specific last 100
        }
    }

    public function getRecentLinks(int $limit = 10): array
    {
        $data = Redis::lRange(self::KEY_LINKS, 0, $limit - 1);
        return array_map(fn($item) => json_decode($item, true), $data);
    }

    public function getRecentLinksForUniverse(int $universeId, int $limit = 10): array
    {
        $key = sprintf('worldos:causality:links:%d', $universeId);
        $data = Redis::lRange($key, 0, $limit - 1);
        return array_map(fn($item) => json_decode($item, true), $data);
    }
}

