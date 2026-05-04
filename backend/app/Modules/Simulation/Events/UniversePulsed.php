<?php

namespace App\Modules\Simulation\Events;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UniversePulsed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Universe $universe,
        public UniverseSnapshot $snapshot
    ) {}

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'universe_id' => $this->universe->id,
            'world_id' => $this->universe->world_id,
            'tick' => $this->snapshot->tick,
            'entropy' => $this->snapshot->entropy,
            'stability_index' => $this->snapshot->stability_index,
            'metrics' => $this->snapshot->metrics,
            'pulsed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("universes.{$this->universe->id}"),
            new Channel("worlds.{$this->universe->world_id}"),
        ];
    }
}

