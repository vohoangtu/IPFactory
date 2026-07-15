<?php

namespace App\Modules\Simulation\Events;

use App\Support\Broadcasting\EmitsWorldEvent;
use App\Support\Broadcasting\WorldEventBroadcast;
use App\Support\Broadcasting\WorldEventEnvelope;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SimulationEventStreamReceived implements ShouldBroadcastNow, WorldEventBroadcast
{
    use Dispatchable, SerializesModels, EmitsWorldEvent;

    public function __construct(
        public int $universeId,
        public int $tick,
        public string $type,
        public array $payload,
        public string $occurredAt
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("universes:{$this->universeId}")];
    }

    public function broadcastAs(): string
    {
        return 'simulation.event';
    }

    protected function toEnvelope(): WorldEventEnvelope
    {
        return new WorldEventEnvelope(
            type: 'simulation.event',
            tick: $this->tick,
            universeId: $this->universeId,
            payload: ['stream_type' => $this->type, 'data' => $this->payload],
            occurredAt: $this->occurredAt,
        );
    }
}
