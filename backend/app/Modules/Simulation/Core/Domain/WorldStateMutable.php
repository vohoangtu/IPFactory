<?php

namespace App\Modules\Simulation\Core\Domain;

/**
 * Mutable world state used internally by EffectResolver when applying effects.
 * Not exposed to engines; engines only see WorldState and emit Effect[].
 */
final class WorldStateMutable
{
    public function __construct(
        private int $universeId,
        private int $tick,
        private array $metrics,
        private array $stateVector,
    ) {
    }

    public static function fromWorldState(WorldState $state): self
    {
        return new self(
            $state->getUniverseId(),
            $state->getTick(),
            $state->getMetrics(),
            $state->getStateVector(),
        );
    }

    public function getUniverseId(): int
    {
        return $this->universeId;
    }

    public function getTick(): int
    {
        return $this->tick;
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function getStateVector(): array
    {
        return $this->stateVector;
    }

    public function getZones(): array
    {
        return $this->stateVector['zones'] ?? [];
    }

    public function setMetrics(array $metrics): void
    {
        $this->metrics = $metrics;
    }

    public function setStateVector(array $stateVector): void
    {
        $this->stateVector = $stateVector;
    }

    public function setZones(array $zones): void
    {
        $this->stateVector['zones'] = $zones;
    }

    public function setPressures(array $pressures): void
    {
        $this->stateVector['pressures'] = $pressures;
    }

    public function setStateVectorKey(string $key, mixed $value): void
    {
        $this->stateVector[$key] = $value;
    }

    /** Build immutable WorldState from current mutable state. */
    public function toWorldState(): WorldState
    {
        return new WorldState(
            $this->universeId,
            $this->tick,
            $this->metrics,
            $this->stateVector,
        );
    }
}
