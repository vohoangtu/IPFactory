<?php

namespace App\Modules\Simulation\Core\Runtime\Systems;

use App\Modules\Simulation\Core\Runtime\Contracts\WorldSystemInterface;
use App\Modules\Simulation\Core\Runtime\Contracts\SimulationStageInterface;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\Causality\ImpactReport;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;

/**
 * StageSystemAdapter – Wraps Simulation Stages to run as World Systems.
 */
class StageSystemAdapter implements WorldSystemInterface
{
    protected SimulationStageInterface $stage;
    protected ?Universe $universe = null;
    protected ?UniverseSnapshot $snapshot = null;

    public function __construct(SimulationStageInterface $stage, ?Universe $universe = null, ?UniverseSnapshot $snapshot = null)
    {
        $this->stage = $stage;
        $this->universe = $universe;
        $this->snapshot = $snapshot;
    }

    public function update(array $context, int $tick): ?ImpactReport
    {
        // Capture stage metadata for reporting
        $report = new ImpactReport(get_class($this->stage), 'Stage', 'Infrastructure');

        // Simulation Stages use Universe/Snapshot, not WorldState context yet.
        $this->stage->run($this->universe ?? new Universe(), $tick, $this->snapshot);

        return null;
    }
}

