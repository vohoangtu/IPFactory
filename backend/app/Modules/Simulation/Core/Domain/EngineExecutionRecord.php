<?php
declare(strict_types=1);

namespace App\Modules\Simulation\Core\Domain;

/**
 * Structured telemetry for a single engine execution within a tick.
 */
final class EngineExecutionRecord
{
    public function __construct(
        public readonly string $engineName,
        public readonly float $elapsedMs,
        public readonly int $effectsCount,
        public readonly int $eventsCount,
        public readonly string $priority,
        public readonly bool $wasSkipped = false,
    ) {
    }

    public function toArray(): array
    {
        return [
            'engine_name'   => $this->engineName,
            'elapsed_ms'    => round($this->elapsedMs, 4),
            'effects_count' => $this->effectsCount,
            'events_count'  => $this->eventsCount,
            'priority'      => $this->priority,
            'was_skipped'   => $this->wasSkipped,
        ];
    }
}
