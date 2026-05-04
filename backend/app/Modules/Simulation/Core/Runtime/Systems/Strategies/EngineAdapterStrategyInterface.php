<?php

namespace App\Modules\Simulation\Core\Runtime\Systems\Strategies;

use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\Causality\ImpactReport;

/**
 * Interface cho Engine Adapter Strategy.
 */
interface EngineAdapterStrategyInterface
{
    /**
     * Kiểm tra xem engine có phù hợp với strategy này không.
     */
    public function canHandle(object $engine): bool;

    /**
     * Thực thi engine.
     */
    public function execute(object $engine, array $context, int $tick, WorldState $state, ImpactReport $report): void;
}
