<?php

namespace App\Modules\Simulation\Actions;

use App\Modules\Simulation\Core\Supervisor\SimulationSupervisor;
use App\Modules\Simulation\Services\Core\SimulationTracer;

/**
 * Facade for advance simulation: delegates to SimulationSupervisor (Phase 2 refactor).
 */
class AdvanceSimulationAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected SimulationSupervisor $supervisor
    ) {}

    public function execute(int $universeId, int $ticks): array
    {
        return SimulationTracer::span('advance_simulation', function () use ($universeId, $ticks) {
            return $this->supervisor->execute($universeId, $ticks);
        });
    }
}


