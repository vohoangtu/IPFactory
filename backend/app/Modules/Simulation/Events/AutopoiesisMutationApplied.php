<?php

namespace App\Modules\Simulation\Events;

use App\Support\Broadcasting\EmitsWorldEvent;
use App\Support\Broadcasting\WorldEventBroadcast;
use App\Support\Broadcasting\WorldEventEnvelope;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AutopoiesisMutationApplied implements ShouldBroadcast, WorldEventBroadcast
{
    use Dispatchable, SerializesModels, EmitsWorldEvent;

    public function __construct(
        public int $universeId,
        public array $payload,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("universes:{$this->universeId}:autopoiesis")];
    }

    public function broadcastAs(): string
    {
        return 'autopoiesis.mutation';
    }

    protected function toEnvelope(): WorldEventEnvelope
    {
        return new WorldEventEnvelope(
            type: 'autopoiesis.mutation',
            tick: (int) ($this->payload['tick'] ?? 0),
            universeId: $this->universeId,
            severity: 'notable',
            payload: $this->payload,
        );
    }
}
