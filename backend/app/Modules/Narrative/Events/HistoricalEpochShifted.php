<?php

namespace App\Modules\Narrative\Events;

use App\Support\Broadcasting\EmitsWorldEvent;
use App\Support\Broadcasting\WorldEventBroadcast;
use App\Support\Broadcasting\WorldEventEnvelope;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HistoricalEpochShifted implements ShouldBroadcast, WorldEventBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;
    use EmitsWorldEvent;

    public function __construct(
        public readonly int $universeId,
        public readonly int $tick,
        public readonly int $zoneId,
        public readonly string $eventType,
        public readonly float $impactScore,
        public readonly array $triggerData
    ) {
        $this->envelope();
    }

    public function broadcastOn(): array
    {
        return [new Channel("universes:{$this->universeId}:narrative")];
    }

    public function broadcastAs(): string
    {
        return 'history.shifted';
    }

    protected function toEnvelope(): WorldEventEnvelope
    {
        return new WorldEventEnvelope(
            type: 'history.shifted',
            tick: $this->tick,
            universeId: $this->universeId,
            severity: 'notable',
            payload: [
                'zone_id' => $this->zoneId,
                'event_type' => $this->eventType,
                'impact_score' => $this->impactScore,
                'trigger_data' => $this->triggerData,
            ],
        );
    }
}
