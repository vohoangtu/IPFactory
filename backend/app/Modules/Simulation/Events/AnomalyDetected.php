<?php

namespace App\Modules\Simulation\Events;

use App\Modules\World\Models\Universe;
use App\Support\Broadcasting\EmitsWorldEvent;
use App\Support\Broadcasting\WorldEventBroadcast;
use App\Support\Broadcasting\WorldEventEnvelope;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnomalyDetected implements ShouldBroadcast, WorldEventBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels, EmitsWorldEvent;

    public function __construct(
        public Universe $universe,
        public array $anomaly
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("universes:{$this->universe->id}:anomaly")];
    }

    public function broadcastAs(): string
    {
        return 'anomaly.detected';
    }

    protected function toEnvelope(): WorldEventEnvelope
    {
        return new WorldEventEnvelope(
            type: 'anomaly.detected',
            tick: (int) $this->universe->current_tick,
            universeId: (int) $this->universe->id,
            worldId: $this->universe->world_id !== null ? (int) $this->universe->world_id : null,
            severity: match (strtolower((string) ($this->anomaly['severity'] ?? ''))) {
                'low', 'info' => 'info',
                'medium', 'warn', 'warning' => 'notable',
                default => 'critical',
            },
            payload: [
                'title' => $this->anomaly['title'] ?? null,
                'description' => $this->anomaly['description'] ?? null,
                'raw_severity' => $this->anomaly['severity'] ?? null,
            ],
        );
    }
}

