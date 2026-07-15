<?php

declare(strict_types=1);

namespace App\Modules\WorldOS\Listeners;

use App\Support\Broadcasting\WorldEventBroadcast;
use Illuminate\Support\Facades\DB;

/**
 * Ghi các sự kiện tường thuật vào world_events để Observatory feed
 * đọc lại được lịch sử (broadcast Centrifugo vốn fire-and-forget).
 */
class PersistWorldEventBroadcast
{
    public function handle(object $event): void
    {
        if (! $event instanceof WorldEventBroadcast) {
            return;
        }

        $envelope = $event->envelope();

        DB::table('world_events')->insert([
            'id' => $envelope->id,
            'universe_id' => $envelope->universeId,
            'tick' => $envelope->tick,
            'type' => $envelope->type,
            'payload' => json_encode([
                'severity' => $envelope->severity,
                'world_id' => $envelope->worldId,
                'occurred_at' => $envelope->occurredAt,
                'data' => $envelope->payload,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
