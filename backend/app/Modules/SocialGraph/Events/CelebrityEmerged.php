<?php

namespace App\Modules\SocialGraph\Events;

use App\Support\Broadcasting\EmitsWorldEvent;
use App\Support\Broadcasting\WorldEventBroadcast;
use App\Support\Broadcasting\WorldEventEnvelope;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CelebrityEmerged implements ShouldBroadcast, WorldEventBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;
    use EmitsWorldEvent;

    public function __construct(
        public readonly int $universeId,
        public readonly int $tick,
        public readonly int $zoneId,
        public readonly int $agentId,
        public readonly float $fame,
        public readonly string $vocation
    ) {
        $this->envelope();
    }

    public function broadcastOn(): array
    {
        return [new Channel("universes:{$this->universeId}:narrative")];
    }

    public function broadcastAs(): string
    {
        return 'celebrity.emerged';
    }

    protected function toEnvelope(): WorldEventEnvelope
    {
        return new WorldEventEnvelope(
            type: 'celebrity.emerged',
            tick: $this->tick,
            universeId: $this->universeId,
            severity: 'notable',
            payload: [
                'zone_id' => $this->zoneId,
                'agent_id' => $this->agentId,
                'fame' => $this->fame,
                'vocation' => $this->vocation,
            ],
        );
    }
}
