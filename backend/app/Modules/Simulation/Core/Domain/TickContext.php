<?php

namespace App\Modules\Simulation\Core\Domain;

/**
 * Context for a simulation tick: universe, tick, seed, optional metadata.
 * Used by SimulationEngine::handle() for deterministic, replayable execution.
 */
final class TickContext
{
    public function __construct(
        private readonly int $universeId,
        private readonly int $tick,
        private readonly int $seed,
        private readonly array $metadata = [],
    ) {
    }

    public function getUniverseId(): int
    {
        return $this->universeId;
    }

    public function getTick(): int
    {
        return $this->tick;
    }

    public function getSeed(): int
    {
        return $this->seed;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }
}
