<?php

namespace App\Modules\Simulation\Services\Society;

use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use App\Modules\Simulation\Jobs\AdvanceUniverseJob;
use Illuminate\Support\Facades\Log;

class AutonomicWorkerService
{
    public function __construct(
        protected WorldRegulatorEngine $worldAutonomicEngine,
        protected \App\Modules\Simulation\Services\Ecology\SurvivalPruningService $pruningService,
        protected MultiverseSchedulerEngine $scheduler
    ) {}

    /**
     * Identify worlds that need ticking, process world-level autonomic logic,
     * and dispatch simulation jobs for active universes.
     * 
     * @param int $ticksPerUniverse Number of ticks to advance each universe by
     * @return int Number of jobs dispatched
     */
    public function pulseAllAutonomicWorlds(int $ticksPerUniverse = 1, bool $shouldPrune = false): int
    {
        if ($shouldPrune) {
            $this->pruningService->prune();
        }

        $autonomicWorlds = World::where('is_autonomic', true)->get();
        $dispatchedCount = 0;

        foreach ($autonomicWorlds as $world) {
            // 1. World-level Autonomic Adjustment (Axiom shifts)
            try {
                $this->worldAutonomicEngine->process($world);
            } catch (\Throwable $e) {
                Log::error("AutonomicWorkerService: WorldAutonomicEngine failed for World {$world->id}: " . $e->getMessage());
            }

            // 2. Schedule: top-N universes by priority (tick_budget from config; 0 = all)
            $tickBudget = (int) config('worldos.scheduler.tick_budget', 0);
            $activeUniverses = $this->scheduler->schedule($world, $tickBudget);

            foreach ($activeUniverses as $universe) {
                // Determine tick speed based on world density/volatility if needed
                $speed = $this->calculateTickSpeed($world, $universe, $ticksPerUniverse);
                
                AdvanceUniverseJob::dispatch($universe->id, $speed);
                $dispatchedCount++;
            }
        }

        if ($dispatchedCount > 0) {
            Log::info("AutonomicWorkerService: Dispatched $dispatchedCount simulation jobs across " . $autonomicWorlds->count() . " autonomic worlds.");
        }

        return $dispatchedCount;
    }

    /**
     * Placeholder for dynamic tick speed calculation.
     */
    private function calculateTickSpeed(World $world, Universe $universe, int $baseTicks): int
    {
        // Future logic: adjust speed based on entropy, energy density, etc.
        return $baseTicks;
    }
}


