<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Actions;

use App\Modules\World\Models\Universe;

use App\Modules\Simulation\Core\Runtime\State\StateManager;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Services\Dashboard\AscensionStatusProjector;
use App\Modules\Simulation\Services\Dashboard\ConsciousnessFieldProjector;
use App\Modules\Simulation\Services\Dashboard\InformationalMassProjector;
use App\Modules\Simulation\Services\Dashboard\TopologyProjector;
use App\Modules\Simulation\Services\Dashboard\WavefunctionProjector;

/**
 * Phase 77: Apex Observer — Dashboard Data Aggregation
 *
 * Thin facade that delegates dashboard projections to dedicated projectors.
 */
class QueryObserverDashboardAction implements \App\Contracts\ActionInterface
{
    public function __construct(
        protected StateManager $stateManager,
        protected WavefunctionProjector $wavefunctionProjector,
        protected InformationalMassProjector $informationalMassProjector,
        protected TopologyProjector $topologyProjector,
        protected ConsciousnessFieldProjector $consciousnessFieldProjector,
        protected AscensionStatusProjector $ascensionStatusProjector,
    ) {}

    public function projectWavefunction(int $universeId): ?array
    {
        $state = $this->ensureStateLoaded($universeId);
        return $state ? $this->wavefunctionProjector->project($state, $universeId) : null;
    }

    public function getInformationalMass(int $universeId): ?array
    {
        $state = $this->ensureStateLoaded($universeId);
        return $state ? $this->informationalMassProjector->project($state, $universeId) : null;
    }

    public function getTopology(int $universeId): ?array
    {
        $state = $this->ensureStateLoaded($universeId);
        return $state ? $this->topologyProjector->project($state, $universeId) : null;
    }

    public function getConsciousnessField(int $universeId): ?array
    {
        $state = $this->ensureStateLoaded($universeId);
        return $state ? $this->consciousnessFieldProjector->project($state, $universeId) : null;
    }

    public function getAscensionStatus(int $universeId): ?array
    {
        $state = $this->ensureStateLoaded($universeId);
        return $state ? $this->ascensionStatusProjector->project($state, $universeId) : null;
    }

    protected function ensureStateLoaded(int $universeId): ?WorldState
    {
        $state = $this->stateManager->get();

        if (!$state || (int) $state->get('universe_id') !== $universeId) {
            $universe = \App\Modules\World\Models\Universe::find($universeId);
            if (!$universe) {
                return null;
            }
            $state = $this->stateManager->load($universe);
        }

        return $state;
    }
}
