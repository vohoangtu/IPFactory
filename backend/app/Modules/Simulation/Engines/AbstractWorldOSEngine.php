<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Engines;

use App\Contracts\WorldOSEngineInterface;
use App\Modules\Simulation\Core\Domain\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Enums\SimulationPhase;

/**
 * AbstractWorldOSEngine — Base class for all WorldOS simulation engines.
 *
 * Provides a standardised lifecycle and interface for engines registered
 * with the PhaseRegistry. Each engine declares which phase it belongs to,
 * its execution priority within that phase, and implements an `execute()`
 * method that returns an EngineResult.
 *
 * Engines MUST NOT mutate the database directly; all mutations flow
 * through the returned EngineResult and are applied by the kernel.
 */
abstract class AbstractWorldOSEngine implements WorldOSEngineInterface
{
    /**
     * Unique human-readable identifier for this engine (e.g. "entropy-engine").
     */
    abstract public function name(): string;

    /**
     * The simulation phase this engine belongs to.
     */
    abstract public function phase(): SimulationPhase;

    /**
     * Evaluate the current world state and return the result.
     *
     * Engines receive the full WorldState (read-only by convention) and a
     * TickContext carrying tick metadata. They MUST return an EngineResult
     * containing any state mutations, events, and metrics.
     */
    abstract public function execute(WorldState $state, TickContext $ctx): EngineResult;

    /**
     * Execution priority within the phase (lower = runs first).
     * Engines with equal priority are ordered alphabetically by name().
     */
    public function priority(): int
    {
        return 0;
    }

    /**
     * Whether this engine is active under the given world configuration.
     * Returning false causes the PhaseRegistry to skip it entirely.
     */
    public function isEnabled(array $config = []): bool
    {
        return true;
    }
}
