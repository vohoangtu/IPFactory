<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Modules\Simulation\Core\Domain\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Enums\SimulationPhase;

/**
 * Contract for WorldOS simulation engines (v2 — PhaseRegistry model).
 *
 * All engines registered with the PhaseRegistry MUST implement this
 * interface (typically by extending AbstractWorldOSEngine).
 */
interface WorldOSEngineInterface
{
    /**
     * Unique human-readable identifier for this engine.
     */
    public function name(): string;

    /**
     * The simulation phase this engine belongs to.
     */
    public function phase(): SimulationPhase;

    /**
     * Evaluate the current world state and return the result.
     */
    public function execute(WorldState $state, TickContext $ctx): EngineResult;

    /**
     * Execution priority within the phase (lower = runs first).
     */
    public function priority(): int;

    /**
     * Whether this engine is active under the given world configuration.
     */
    public function isEnabled(array $config = []): bool;
}
