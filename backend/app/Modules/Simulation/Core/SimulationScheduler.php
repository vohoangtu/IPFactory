<?php

namespace App\Modules\Simulation\Core;

use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Runtime\Contracts\TickSchedulerInterface;

/**
 * Simulation Scheduler (Doc §4.3, §23, RÀ_SOÁT_TMP mục 1).
 *
 * Single place for universe-level tick assignment: which stages run at which tick,
 * and which engines are active at a given tick (by tickRate). Composes EngineRegistry
 * and TickScheduler; does not replace WorldKernel or SimulationTickPipeline.
 */
final class SimulationScheduler
{
    public function __construct(
        private readonly EngineRegistry $registry,
        private readonly TickSchedulerInterface $tickScheduler
    ) {
    }

    /**
     * Engines that should run at this tick (ordered by phase/priority, filtered by tick % tickRate === 0).
     *
     * @return SimulationEngine[]
     */
    public function enginesActiveAtTick(int $tick): array
    {
        $active = [];
        foreach ($this->registry->getOrdered() as $engine) {
            $rate = $engine->tickRate();
            if ($rate >= 1 && ($tick % $rate) === 0) {
                $active[] = $engine;
            }
        }
        return $active;
    }

    /**
     * Stage order for the tick pipeline (from config / TickScheduler).
     *
     * @return string[]
     */
    public function stageOrder(): array
    {
        return $this->tickScheduler->stageOrder();
    }

    /**
     * Whether the given stage should run at this tick (interval from config).
     */
    public function shouldRunStage(string $stageKey, int $tick): bool
    {
        return $this->tickScheduler->shouldRun($stageKey, $tick);
    }

    /**
     * Engine manifest for deterministic replay (Doc §26).
     *
     * @return array<string, string>
     */
    public function getManifest(): array
    {
        return $this->registry->getManifest();
    }

    public function getRegistry(): EngineRegistry
    {
        return $this->registry;
    }
}
