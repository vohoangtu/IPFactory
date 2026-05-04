<?php

namespace App\Modules\Simulation\Core\Runtime\Contracts;

use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\Causality\ImpactReport;

interface WorldSystemInterface
{
    /**
     * Update the world state and return a mandatory ImpactReport for semantic history (§V81).
     */
    public function update(array $context, int $tick): ?ImpactReport;
}
