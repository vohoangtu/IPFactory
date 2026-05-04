<?php

namespace App\Modules\Simulation\Events;

use App\Modules\World\Models\World;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PowerSystemTransitionTriggered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly World $world,
        public readonly string $targetPowerSystem,
        public readonly array $transitionContext = []
    ) {}

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'world_id' => $this->world->id,
            'target_power_system' => $this->targetPowerSystem,
            'context' => $this->transitionContext,
            'triggered_at' => now()->toIso8601String(),
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
            new Channel("worlds.{$this->world->id}"),
        ];
    }
}
