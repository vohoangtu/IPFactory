<?php

namespace App\Modules\Simulation\Core\Runtime\Systems;

use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\Contracts\WorldSystemInterface;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\Causality\ImpactReport;

/**
 * EngineSystemAdapter – Wraps Engines (including SimulationEngine interface)
 * 
 * Allows engines to be orchestrated by the WorldKernel.
 */
class EngineSystemAdapter implements WorldSystemInterface
{
    protected object $engine;
    protected array $strategies = [];

    public function __construct(object $engine)
    {
        $this->engine = $engine;
        $this->strategies = [
            new Strategies\ModernEngineStrategy(),
            new Strategies\LegacyRunStrategy(),
            new Strategies\LegacyUpdateStrategy(),
        ];
    }

    public function update(array $context, int $tick): ?ImpactReport
    {
        $report = new ImpactReport(get_class($this->engine), 'Hybrid', 'Engine');
        $state = WorldState::fromArray($context);

        foreach ($this->strategies as $strategy) {
            if ($strategy->canHandle($this->engine)) {
                $strategy->execute($this->engine, $context, $tick, $state, $report);

                // V9: Extract mutations from modified state and inject into Report
                $diff = $state->getDiff($context);
                if (!empty($diff)) {
                    $report->log(
                        'engine',
                        get_class($this->engine),
                        'mutation',
                        'world_state',
                        $state->getUniverseId(),
                        1.0,
                        1.0,
                        ['mutation' => $diff]
                    );
                }

                return $report;
            }
        }

        return null; 
    }
}
