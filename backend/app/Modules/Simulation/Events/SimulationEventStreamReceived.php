<?php

namespace App\Modules\Simulation\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SimulationEventStreamReceived implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $universeId,
        public int $tick,
        public string $type,
        public array $payload,
        public string $occurredAt
    ) {}

    public function broadcastOn(): array
    {
        // Broadcast specifically to the universe channel, but can also use public:universes channel
        return ['public:universes'];
    }

    public function broadcastAs(): string
    {
        return 'simulation_event_stream_received';
    }
}
