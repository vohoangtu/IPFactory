<?php

namespace App\Modules\Simulation\Events;

use App\Modules\World\Models\Universe;
use App\Modules\World\Models\Epoch;
use App\Support\Broadcasting\EmitsWorldEvent;
use App\Support\Broadcasting\WorldEventBroadcast;
use App\Support\Broadcasting\WorldEventEnvelope;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EpochTransitioned implements ShouldBroadcast, WorldEventBroadcast
{
    use Dispatchable, SerializesModels, EmitsWorldEvent;

    public function __construct(
        public Universe $universe,
        public Epoch $oldEpoch,
        public Epoch $newEpoch,
        public int $tick
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("universes:{$this->universe->id}"), 'public:universes'];
    }

    public function broadcastAs(): string
    {
        return 'epoch.transitioned';
    }

    protected function toEnvelope(): WorldEventEnvelope
    {
        return new WorldEventEnvelope(
            type: 'epoch.transitioned',
            tick: $this->tick,
            universeId: (int) $this->universe->id,
            worldId: $this->universe->world_id !== null ? (int) $this->universe->world_id : null,
            severity: 'notable',
            payload: [
                'old_epoch' => ['id' => $this->oldEpoch->id, 'name' => $this->oldEpoch->name],
                'new_epoch' => ['id' => $this->newEpoch->id, 'name' => $this->newEpoch->name],
            ],
        );
    }
}
