<?php

namespace App\Modules\Narrative\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\Channel;

class HistoricalEpochShifted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $universeId,
        public readonly int $tick,
        public readonly int $zoneId,
        public readonly string $eventType,
        public readonly float $impactScore,
        public readonly array $triggerData
    ) {}

    public function broadcastOn()
    {
        return new Channel("universe.{$this->universeId}.narrative");
    }

    public function broadcastAs()
    {
        return 'HistoricalEpochShifted';
    }
}
