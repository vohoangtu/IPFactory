<?php

namespace App\Modules\Simulation\Events;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UniverseSimulationPulsed implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Universe $universe,
        public UniverseSnapshot $snapshot,
        public array $engineResponse = [],
        public array $engineEvents = []
    ) {}

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
}

