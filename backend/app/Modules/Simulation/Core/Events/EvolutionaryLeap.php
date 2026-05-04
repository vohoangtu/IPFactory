<?php

namespace App\Modules\Simulation\Core\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * EvolutionaryLeap — Event bắn ra khi vũ trụ đạt bước nhảy tiến hóa quan trọng.
 * 
 * Được dispatch khi xảy ra: phase transition (Stone → Bronze → Iron → ...),
 * singularity, hoặc ascension. Các Listener có thể: tạo Chronicle,
 * cập nhật Dashboard, hoặc trigger narrative generation.
 */
class EvolutionaryLeap
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $universeId,
        public readonly int $tick,
        public readonly string $leapType,
        public readonly string $fromPhase,
        public readonly string $toPhase,
        public readonly array $metadata = [],
    ) {}
}
