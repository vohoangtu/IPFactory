<?php

namespace App\Modules\Simulation\Core\Runtime\Contracts;

interface TickSchedulerInterface
{
    /**
     * Whether the given stage should run at this tick (e.g. economy every 10, war every 50).
     */
    public function shouldRun(string $stageKey, int $tick): bool;

    /**
     * Ordered list of stage keys for the pipeline (e.g. ['actor', 'culture', 'civilization', ...]).
     */
    public function stageOrder(): array;
}
