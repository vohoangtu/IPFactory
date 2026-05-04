<?php

namespace App\Modules\Simulation\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AutopoiesisMutationApplied implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $universeId,
        public array $payload,
    ) {}

    public function broadcastWith(): array
    {
        return $this->payload;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("universes.{$this->universeId}.autopoiesis"),
        ];
    }
}
