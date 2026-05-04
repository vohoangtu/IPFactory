<?php

namespace App\Modules\Simulation\Core\Runtime;

use App\Modules\Simulation\Core\Runtime\Contracts\TickSchedulerInterface;
use App\Modules\Simulation\Services\Cosmology\SimulationClock;

/**
 * PhaseScheduler – implements 5-phase execution loop.
 */
final class PhaseScheduler implements TickSchedulerInterface
{
    public function __construct(
        protected SimulationClock $clock
    ) {}

    public function shouldRun(string $stageKey, int $tick): bool
    {
        $phase = $this->getPhaseOfStage($stageKey);
        $eligiblePhases = $this->clock->getEligiblePhases($tick);
        
        return in_array($phase, $eligiblePhases);
    }

    public function stageOrder(): array
    {
        // Strictly ordered by Phase
        return [
            // Phase: environment
            'rule', 'environment', 'physics', 'cosmic', 
            // Phase: life
            'population', 'ecology', 'agriculture', 'disease',
            // Phase: mind
            'vector_actor', 'actor', 'intel', 'decision',
            // Phase: social (Cycles)
            'civilization', 'economy', 'politics', 'culture', 'field',
            // Phase: meta (Historical)
            'war', 'crisis', 'event', 'meta', 'history', 'narrative'
        ];
    }

    protected function getPhaseOfStage(string $stageKey): string
    {
        return match ($stageKey) {
            'rule', 'environment', 'physics', 'cosmic' => 'environment',
            'population', 'ecology', 'agriculture', 'disease' => 'life',
            'vector_actor', 'actor', 'intel', 'decision' => 'mind',
            'civilization', 'economy', 'politics', 'culture', 'field' => 'social',
            'war', 'crisis', 'event', 'meta', 'history', 'narrative' => 'meta',
            default => 'meta' // Safety fallback
        };
    }
}

