<?php

namespace App\Modules\Simulation\Events;

use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\World\Models\Universe;
use App\Support\Broadcasting\EmitsWorldEvent;
use App\Support\Broadcasting\WorldEventBroadcast;
use App\Support\Broadcasting\WorldEventEnvelope;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UniverseSimulationPulsed implements ShouldBroadcast, WorldEventBroadcast
{
    use Dispatchable;
    use SerializesModels;
    use EmitsWorldEvent;

    public function __construct(
        public Universe $universe,
        public UniverseSnapshot $snapshot,
        public array $engineResponse = [],
        public array $engineEvents = []
    ) {
        $this->envelope();
    }

    public function broadcastOn(): array
    {
        return ['public:universes'];
    }

    public function broadcastAs(): string
    {
        return 'pulsed';
    }

    /**
     * Do not broadcast when snapshot is virtual (not persisted); avoids serialization and findOrFail on null id.
     */
    public function broadcastWhen(): bool
    {
        return $this->snapshot->exists;
    }

    protected function toEnvelope(): WorldEventEnvelope
    {
        return new WorldEventEnvelope(
            type: 'pulsed',
            tick: (int) $this->snapshot->tick,
            universeId: (int) $this->universe->id,
            worldId: $this->universe->world_id !== null ? (int) $this->universe->world_id : null,
            payload: [
                'entropy' => $this->snapshot->entropy,
                'stability_index' => $this->snapshot->stability_index,
                'engine_events_count' => count($this->engineEvents),
            ],
        );
    }
}
