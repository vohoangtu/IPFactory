<?php

namespace App\Modules\Simulation\Core\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * StabilityCompromised — Event bắn ra khi hệ thống mô phỏng mất ổn định.
 * 
 * Được dispatch khi Entropy vượt ngưỡng hoặc Stability Index giảm mạnh.
 * Các Listener có thể: ghi log, gửi notification, trigger fork, hoặc auto-archive.
 */
class StabilityCompromised
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $universeId,
        public readonly int $tick,
        public readonly float $entropy,
        public readonly float $stabilityIndex,
        public readonly string $reason,
    ) {}
}
