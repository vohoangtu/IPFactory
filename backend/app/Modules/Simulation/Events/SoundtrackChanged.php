<?php

namespace App\Modules\Simulation\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SoundtrackChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $url;
    public string $epochName;
    public string $style;

    /**
     * Create a new event instance.
     */
    public function __construct(string $url, string $epochName, string $style)
    {
        $this->url = $url;
        $this->epochName = $epochName;
        $this->style = $style;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Kênh toàn cầu của vũ trụ
        return [
            new Channel('global_universe'),
        ];
    }

    /**
     * Tên Event khi nhận ở Frontend
     */
    public function broadcastAs(): string
    {
        return 'SoundtrackChanged';
    }
}
