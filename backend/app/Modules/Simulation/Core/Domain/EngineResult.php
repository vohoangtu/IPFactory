<?php

namespace App\Modules\Simulation\Core\Domain;

use App\Modules\Simulation\Core\Contracts\Effect;

/**
 * Result of an engine tick: events to publish, state changes (effects), and metrics.
 * Engine must not mutate state directly; Kernel applies stateChanges via EffectResolver.
 */
final class EngineResult
{
    /** @var object[] WorldEvent-like DTOs or arrays for Event Bus */
    public array $events = [];

    /** @var Effect[] Effects to apply to WorldState */
    public array $stateChanges = [];

    /** @var array<string, mixed> Metrics for analytics / AEE */
    public array $metrics = [];

    /** @var array<string, int> Causal parent mapping (e.g. event_id => parent_chronicle_id) */
    public array $causalLinks = [];

    /** Whether this engine was skipped (no work to do). */
    public bool $skipped = false;

    /** Reason for skipping (only meaningful when $skipped is true). */
    public string $skipReason = '';

    public function __construct(
        array $events = [],
        array $stateChanges = [],
        array $metrics = [],
        array $causalLinks = [],
        bool $skipped = false,
        string $skipReason = '',
    ) {
        $this->events = $events;
        $this->stateChanges = $stateChanges;
        $this->metrics = $metrics;
        $this->causalLinks = $causalLinks;
        $this->skipped = $skipped;
        $this->skipReason = $skipReason;
    }

    public static function empty(): self
    {
        return new self([], [], []);
    }

    /**
     * Create a skipped result — the engine had no work to do.
     */
    public static function skipped(string $reason = ''): self
    {
        return new self(skipped: true, skipReason: $reason);
    }

    public static function fromEffects(array $effects, array $events = [], array $metrics = []): self
    {
        return new self($events, $effects, $metrics);
    }

    public function addEvent(array|object $event): self
    {
        $this->events[] = $event;
        return $this;
    }

    /**
     * Link an event type to a parent chronicle ID for Causality 2.0.
     */
    public function linkEvent(string $eventType, int $parentChronicleId): self
    {
        $this->causalLinks[$eventType] = $parentChronicleId;
        return $this;
    }

    /**
     * Get execution duration in milliseconds (from metrics).
     */
    public function getDurationMs(): float
    {
        return (float) ($this->metrics['duration_ms'] ?? $this->metrics['_kernel_elapsed_ms'] ?? 0);
    }

    /**
     * Get count of entities affected by this engine's execution.
     */
    public function getEntitiesAffected(): int
    {
        return (int) ($this->metrics['entities_affected'] ?? count($this->stateChanges));
    }
}
