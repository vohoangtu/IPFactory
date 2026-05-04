<?php

namespace App\Modules\World\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\Channel;

class ArtifactDiscovered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $universeId,
        public readonly int $tick,
        public readonly int $zoneId,
        public readonly int $artifactId,
        public readonly float $mass,
        public readonly float $knowledgeEncoded
    ) {}

    public function broadcastOn()
    {
        return new Channel("universe.{$this->universeId}.narrative");
    }

    public function broadcastAs()
    {
        return 'ArtifactDiscovered';
    }
}
