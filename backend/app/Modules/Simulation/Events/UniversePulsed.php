<?php

namespace App\Modules\Simulation\Events;

use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\World\Models\Universe;
use App\Support\Broadcasting\EmitsWorldEvent;
use App\Support\Broadcasting\WorldEventBroadcast;
use App\Support\Broadcasting\WorldEventEnvelope;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UniversePulsed implements ShouldBroadcast, WorldEventBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;
    use EmitsWorldEvent;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Universe $universe,
        public UniverseSnapshot $snapshot
    ) {
        $this->envelope();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel("universes:{$this->universe->id}")];
    }

    public function broadcastAs(): string
    {
        return 'universe.pulsed';
    }

    protected function toEnvelope(): WorldEventEnvelope
    {
        return new WorldEventEnvelope(
            type: 'universe.pulsed',
            tick: (int) $this->snapshot->tick,
            universeId: (int) $this->universe->id,
            worldId: $this->universe->world_id !== null ? (int) $this->universe->world_id : null,
            payload: [
                'entropy' => $this->snapshot->entropy,
                'stability_index' => $this->snapshot->stability_index,
                'status' => (string) $this->universe->status,
                'metrics' => $this->snapshot->metrics,
            ],
        );
    }
}
