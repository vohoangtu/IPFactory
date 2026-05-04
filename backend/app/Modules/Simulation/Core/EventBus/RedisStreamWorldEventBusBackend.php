<?php

namespace App\Modules\Simulation\Core\EventBus;

use App\Modules\Simulation\Events\SimulationEventOccurred;
use App\Modules\Simulation\Core\Contracts\WorldEventBusBackendInterface;
use App\Modules\Simulation\Core\Events\WorldEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Backend: append to Redis Stream for audit/replay, then persist to DB and dispatch (same as database driver).
 * Stream key: world_events (single stream) or config worldos.event_bus.stream_key.
 */
final class RedisStreamWorldEventBusBackend implements WorldEventBusBackendInterface
{
    private const DEFAULT_STREAM_KEY = 'world_events';
    private const MAX_LEN = 100_000;

    public function __construct(
        private readonly bool $alsoPersistToDb = true,
        private readonly ?string $streamKey = null,
    ) {
    }

    public function publish(WorldEvent $event): void
    {
        $streamKey = $this->streamKey ?? config('worldos.event_bus.stream_key', self::DEFAULT_STREAM_KEY);
        try {
            $payload = [
                'id' => $event->id,
                'universe_id' => (string) $event->universeId,
                'tick' => (string) $event->tick,
                'type' => $event->type,
                'location' => $event->location ?? '',
                'impact_score' => (string) $event->impactScore,
                'payload' => json_encode($event->payload),
                'actors' => json_encode($event->actors),
                'causes' => json_encode($event->causes),
                'at' => now()->toIso8601String(),
            ];
            if (config('database.redis.client') === 'predis') {
                $flat = [];
                foreach ($payload as $k => $v) {
                    $flat[] = $k;
                    $flat[] = (string)$v;
                }
                Redis::connection()->executeRaw(['XADD', $streamKey, 'MAXLEN', '~', (string)self::MAX_LEN, '*', ...$flat]);
            } else {
                Redis::xAdd($streamKey, '*', $payload, self::MAX_LEN);
            }
        } catch (\Throwable $e) {
            Log::warning('WorldEventBus Redis XADD failed: ' . $e->getMessage(), ['event_id' => $event->id]);
        }
        if ($this->alsoPersistToDb) {
            DB::table('world_events')->insert([
                'id' => $event->id,
                'universe_id' => $event->universeId,
                'tick' => $event->tick,
                'type' => $event->type,
                'payload' => json_encode($event->payload),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        Event::dispatch(new SimulationEventOccurred(
            $event->universeId,
            $event->type,
            $event->tick,
            $event->toArray()
        ));
    }
}

