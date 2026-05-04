<?php

namespace App\Modules\Simulation\Services\Meta;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

/**
 * TemporalSyncService: Enforces the 'Absolute Chronos' law (§V21).
 * Ensures all universes within a world are locked to the same Global Tick.
 */
class TemporalSyncService
{
    /**
     * Advance the global clock of a world.
     */
    public function advanceGlobalClock(World $world, int $ticks): void
    {
        $world->increment('global_tick', $ticks);
        Log::info("CHRONOS: World [{$world->id}] global clock advanced to tick {$world->global_tick}.");
    }

    /**
     * Synchronize a universe to the world's master clock.
     */
    public function synchronize(Universe $universe): void
    {
        $world = $universe->world;
        if (!$world) return;
        $masterTick = $world->global_tick;
        
        if ($universe->current_tick !== $masterTick) {
            $universe->update(['current_tick' => $masterTick]);
            Log::debug("CHRONOS: Universe #{$universe->id} synchronized to master tick {$masterTick}.");
        }
    }
}


