<?php

namespace App\Modules\Simulation\Services\Cosmology;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;

/**
 * SimulationClock – The heart of WorldOS time.
 * 
 * Manages:
 * 1. Tick (Daily/Atomic)
 * 2. Cycle (Monthly/Civilizational) - every 30 ticks
 * 3. Epoch Step (Century/Historical) - every 300 ticks
 */
class SimulationClock
{
    public const TICKS_PER_CYCLE = 30;
    public const TICKS_PER_EPOCH = 300;

    public function getCurrentTime(Universe $universe): array
    {
        $tick = (int) $universe->current_tick;
        
        return [
            'tick' => $tick,
            'cycle' => (int) floor($tick / self::TICKS_PER_CYCLE),
            'epoch_step' => (int) floor($tick / self::TICKS_PER_EPOCH),
            'is_cycle_tick' => ($tick % self::TICKS_PER_CYCLE === 0),
            'is_epoch_tick' => ($tick % self::TICKS_PER_EPOCH === 0),
        ];
    }

    /**
     * Determine which engines should run based on the current tick.
     */
    public function getEligiblePhases(int $tick): array
    {
        $phases = ['environment', 'life', 'mind']; // Always run core biology/physics
        
        if ($tick % self::TICKS_PER_CYCLE === 0) {
            $phases[] = 'social'; // Civilization engines
        }
        
        if ($tick % self::TICKS_PER_EPOCH === 0) {
            $phases[] = 'meta'; // Historical/Timeline engines
        }

        return $phases;
    }
}

