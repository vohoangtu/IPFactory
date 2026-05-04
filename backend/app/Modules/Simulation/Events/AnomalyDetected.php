<?php

namespace App\Modules\Simulation\Events;

use App\Modules\World\Models\Universe;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnomalyDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Universe $universe,
        public array $anomaly
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('universe.' . $this->universe->id),
            new Channel('simulation.alerts')
        ];
    }

    public function broadcastAs(): string
    {
        return 'anomaly.detected';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => uniqid('anomaly_'),
            'universe_id' => $this->universe->id,
            'title' => $this->anomaly['title'],
            'description' => $this->anomaly['description'],
            'severity' => $this->anomaly['severity'],
            'tick' => $this->universe->current_tick,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

