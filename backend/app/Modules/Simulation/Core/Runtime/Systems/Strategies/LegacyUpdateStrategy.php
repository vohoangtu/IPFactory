<?php

namespace App\Modules\Simulation\Core\Runtime\Systems\Strategies;

use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\Causality\ImpactReport;

use App\Modules\Simulation\Core\Runtime\Systems\Strategies\EngineAdapterStrategyInterface;

/**
 * Strategy dành cho các Engine cũ có method update().
 */
class LegacyUpdateStrategy implements EngineAdapterStrategyInterface
{
    public function canHandle(object $engine): bool
    {
        return method_exists($engine, 'update');
    }

    public function execute(object $engine, array $context, int $tick, WorldState $state, ImpactReport $report): void
    {
        $engine->update($state, $tick);
        $report->log('Engine', get_class($engine), 'legacy_update', 'WorldState', 'global');
    }
}
