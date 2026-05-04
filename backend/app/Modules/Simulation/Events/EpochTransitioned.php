<?php

namespace App\Modules\Simulation\Events;

use App\Modules\World\Models\Universe;
use App\Modules\World\Models\Epoch;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EpochTransitioned implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Universe $universe,
        public Epoch $oldEpoch,
        public Epoch $newEpoch,
        public int $tick
    ) {}

    public function broadcastOn(): array
    {
        return ['public:universes'];
    }

    public function broadcastAs(): string
    {
        return 'epoch.transitioned';
    }
}
