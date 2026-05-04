<?php

namespace App\Modules\Simulation\Core\Runtime\State;

/**
 * Phase 5: Read-Only Wrapper for WorldState.
 * Prevents engines from mutating state directly via set() methods.
 * Engines must return Effects to change the world.
 */
final class ReadOnlyWorldState extends WorldState
{
    private WorldState $inner;

    public function __construct(WorldState $inner)
    {
        parent::__construct(
            $inner->toArray(),
            $inner->neighboring_realities,
            $inner->legacy_data,
            $inner->hyperspace_vector,
            $inner->nested_realities
        );
        $this->inner = $inner;
    }

    /**
     * Override all set methods to throw exceptions.
     */
    public function set(string $key, mixed $value): void
    {
        throw new \RuntimeException("Architecture Violation: Engine attempted to mutate state key '{$key}' directly. Engines must return Effects.");
    }

    public function setCosmic(array $val): void { $this->fail(); }
    public function setPlanetary(array $val): void { $this->fail(); }
    public function setEcosystem(array $val): void { $this->fail(); }
    public function setActors(array $val): void { $this->fail(); }
    public function setCivilization(array $val): void { $this->fail(); }
    public function setFields(array $val): void { $this->fail(); }
    public function setStabilityIndex(float $val): void { $this->fail(); }
    public function setScars(array $scars): void { $this->fail(); }
    public function setMetadata(array $meta): void { $this->fail(); }

    private function fail(): void
    {
        throw new \RuntimeException("Architecture Violation: Engine attempted to mutate state directly. Engines must return Effects.");
    }

    // Proxy read methods to inner state
    public function get(string $key, mixed $default = null): mixed { return $this->inner->get($key, $default); }
    public function getTick(): int { return $this->inner->getTick(); }
    public function getFields(): array { return $this->inner->getFields(); }
    public function getEcosystem(): array { return $this->inner->getEcosystem(); }
    public function getPlanetary(): array { return $this->inner->getPlanetary(); }
    public function getCosmic(): array { return $this->inner->getCosmic(); }
    public function getActors(): array { return $this->inner->getActors(); }
    public function getCivilization(): array { return $this->inner->getCivilization(); }
    public function getStabilityIndex(): float { return $this->inner->getStabilityIndex(); }
    public function getEntropy(): float { return $this->inner->getEntropy(); }
    public function getStateVector(): array { return $this->inner->getStateVector(); }
    public function getHyperspaceVector(): array { return $this->inner->getHyperspaceVector(); }
}
