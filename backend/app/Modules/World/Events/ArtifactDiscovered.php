<?php

namespace App\Modules\World\Events;

use App\Support\Broadcasting\EmitsWorldEvent;
use App\Support\Broadcasting\WorldEventBroadcast;
use App\Support\Broadcasting\WorldEventEnvelope;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ArtifactDiscovered implements ShouldBroadcast, WorldEventBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;
    use EmitsWorldEvent;

    public function __construct(
        public readonly int $universeId,
        public readonly int $tick,
        public readonly int $zoneId,
        public readonly int $artifactId,
        public readonly float $mass,
        public readonly float $knowledgeEncoded
    ) {
        $this->envelope();
    }

    public function broadcastOn(): array
    {
        return [new Channel("universes:{$this->universeId}:narrative")];
    }

    public function broadcastAs(): string
    {
        return 'artifact.discovered';
    }

    protected function toEnvelope(): WorldEventEnvelope
    {
        return new WorldEventEnvelope(
            type: 'artifact.discovered',
            tick: $this->tick,
            universeId: $this->universeId,
            severity: 'notable',
            payload: [
                'zone_id' => $this->zoneId,
                'artifact_id' => $this->artifactId,
                'mass' => $this->mass,
                'knowledge_encoded' => $this->knowledgeEncoded,
            ],
        );
    }
}
