<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Engines;

use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Domain\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Enums\EngineAuthority;
use App\Modules\Simulation\Enums\SimulationPhase;

/**
 * LegacyEngineAdapter — Wraps a legacy SimulationEngine implementation
 * so it can be registered with PhaseRegistry as an AbstractWorldOSEngine.
 *
 * This adapter translates the old handle()-based interface into the new
 * execute()-based interface, bridging the migration gap without requiring
 * every engine to be rewritten.
 *
 * Usage:
 *   $registry->register(new LegacyEngineAdapter($existingEngine, SimulationPhase::Environment));
 */
class LegacyEngineAdapter extends AbstractWorldOSEngine
{
    public function __construct(
        private readonly SimulationEngine $engine,
        private readonly SimulationPhase $simulationPhase,
        private readonly EngineAuthority $authority = EngineAuthority::SUPPLEMENT,
    ) {
    }

    public function getAuthority(): EngineAuthority
    {
        return $this->authority;
    }

    public function name(): string
    {
        return $this->engine->name();
    }

    public function phase(): SimulationPhase
    {
        return $this->simulationPhase;
    }

    public function priority(): int
    {
        return $this->engine->priority();
    }

    public function execute(WorldState $state, TickContext $ctx): EngineResult
    {
        // Delegate to the legacy engine's handle() method.
        // Legacy engines return Core\Engines\EngineResult but Domain\EngineResult
        // may be a different class, so we translate.
        $legacyResult = $this->engine->handle($state, $ctx);

        // The EngineResult from Core\Domain is what the engine returns
        // via the SimulationEngine contract. It's the same class.
        if ($legacyResult instanceof EngineResult) {
            return $legacyResult;
        }

        // If the legacy result is from Core\Engines\EngineResult (different namespace),
        // translate it to Domain\EngineResult.
        return new EngineResult(
            events: $legacyResult->events ?? [],
            stateChanges: $legacyResult->stateChanges ?? [],
            metrics: $legacyResult->metrics ?? [],
            causalLinks: $legacyResult->causalLinks ?? [],
        );
    }

    public function isEnabled(array $config = []): bool
    {
        return true;
    }
}
