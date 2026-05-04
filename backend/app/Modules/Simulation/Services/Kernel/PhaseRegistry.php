<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Services\Kernel;

use App\Modules\Simulation\Engines\AbstractWorldOSEngine;
use App\Modules\Simulation\Engines\LegacyEngineAdapter;
use App\Modules\Simulation\Enums\EngineAuthority;
use App\Modules\Simulation\Enums\SimulationPhase;

/**
 * PhaseRegistry — Manages registration and retrieval of simulation engines
 * organised by their declared SimulationPhase.
 *
 * Engines are registered once (typically in a ServiceProvider) and then
 * queried by the WorldKernel during tick execution.
 *
 * Within each phase, engines are sorted by priority (ascending).
 * Engines with equal priority are ordered alphabetically by name.
 * Engines that report isEnabled() === false are excluded from retrieval.
 * Duplicate engine names are rejected at registration time.
 */
class PhaseRegistry
{
    /**
     * @var array<int, AbstractWorldOSEngine[]> Keyed by SimulationPhase backing value.
     */
    private array $engines = [];

    /**
     * Track registered engine names globally to detect duplicates.
     *
     * @var array<string, true>
     */
    private array $registeredNames = [];

    /**
     * Whether the internal sort cache is stale after a registration.
     *
     * @var array<int, bool>
     */
    private array $dirty = [];

    /**
     * Register an engine. Throws on duplicate name.
     *
     * @throws \InvalidArgumentException If an engine with the same name is already registered.
     */
    public function register(AbstractWorldOSEngine $engine): void
    {
        $name = $engine->name();

        if (isset($this->registeredNames[$name])) {
            throw new \InvalidArgumentException(
                "Duplicate engine name: '{$name}'. Each engine must have a unique name."
            );
        }

        $phaseValue = $engine->phase()->value;

        $this->engines[$phaseValue][] = $engine;
        $this->registeredNames[$name] = true;
        $this->dirty[$phaseValue] = true;
    }

    /**
     * Get all enabled engines for a phase, sorted by priority then name.
     *
     * When $rustAuthoritative is true, engines with authority OVERLAP or BRIDGE
     * are excluded — Rust is the authoritative source for those computations.
     *
     * @return AbstractWorldOSEngine[]
     */
    public function getEnginesForPhase(SimulationPhase $phase, array $config = [], bool $rustAuthoritative = false): array
    {
        $phaseValue = $phase->value;

        if (!isset($this->engines[$phaseValue])) {
            return [];
        }

        // Sort if dirty
        if (!empty($this->dirty[$phaseValue])) {
            usort($this->engines[$phaseValue], function (AbstractWorldOSEngine $a, AbstractWorldOSEngine $b): int {
                $priorityCmp = $a->priority() <=> $b->priority();
                if ($priorityCmp !== 0) {
                    return $priorityCmp;
                }
                return strcmp($a->name(), $b->name());
            });
            $this->dirty[$phaseValue] = false;
        }

        // Filter by isEnabled + authority
        return array_values(
            array_filter(
                $this->engines[$phaseValue],
                function (AbstractWorldOSEngine $engine) use ($config, $rustAuthoritative): bool {
                    if (!$engine->isEnabled($config)) {
                        return false;
                    }

                    // When Rust is authoritative, skip OVERLAP and BRIDGE engines
                    if ($rustAuthoritative && $engine instanceof LegacyEngineAdapter) {
                        $authority = $engine->getAuthority();
                        if ($authority === EngineAuthority::OVERLAP || $authority === EngineAuthority::BRIDGE) {
                            return false;
                        }
                    }

                    return true;
                }
            )
        );
    }

    /**
     * Get all phases that have at least one registered engine, in canonical order.
     *
     * @return SimulationPhase[]
     */
    public function getAllPhases(): array
    {
        $phases = [];
        foreach (SimulationPhase::inOrder() as $phase) {
            if (!empty($this->engines[$phase->value])) {
                $phases[] = $phase;
            }
        }
        return $phases;
    }

    /**
     * Get all registered engine names (for debugging / introspection).
     *
     * @return string[]
     */
    public function getRegisteredNames(): array
    {
        return array_keys($this->registeredNames);
    }

    /**
     * Total count of registered engines across all phases.
     */
    public function count(): int
    {
        $total = 0;
        foreach ($this->engines as $phaseEngines) {
            $total += count($phaseEngines);
        }
        return $total;
    }
}
