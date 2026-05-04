<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Simulation\Entities\UniverseEntity;
use App\Modules\Simulation\Models\UniverseSnapshot;
use Illuminate\Support\Facades\Log;

/**
 * NarrativeScheduler: Determines if a narrative "tick" should trigger an LLM call.
 * Implements the Adaptive Tick strategy for cost and performance optimization.
 */
class NarrativeScheduler
{
    /**
     * Should we run the narrative pulse for this universe?
     */
    public function shouldPulse(UniverseEntity $universe, UniverseSnapshot $snapshot): bool
    {
        // 1. Minimum Tick Interval Check (Optimization - avoid every-tick calls)
        $minInterval = (int) config('worldos.narrative.min_tick_interval', 10);
        
        $lastPulseTick = \App\Modules\Narrative\Models\NarrativeState::where('universe_id', $universe->id)->value('last_tick') ?? 0;

        if (($snapshot->tick - $lastPulseTick) < $minInterval) {
            return false;
        }

        // 2. Force run on major events
        if (!empty($snapshot->metrics['major_events'] ?? [])) {
            Log::debug("NarrativeScheduler: Major event detected at tick {$snapshot->tick}. Forcing pulse.");
            return true;
        }

        // 3. Check for significant state changes (Threshold Delta)
        $threshold = (float) config('worldos.narrative.delta_threshold', 0.05);
        
        $prevSnapshot = UniverseSnapshot::where('universe_id', $universe->id)
            ->where('tick', '<', $snapshot->tick)
            ->orderByDesc('tick')
            ->first();

        if (!$prevSnapshot) {
            return true; // Always run for the first time
        }

        $entropyDelta = abs(($universe->entropy ?? 0) - ($prevSnapshot->entropy ?? 0));
        $stabilityDelta = abs(($universe->stabilityIndex ?? 0) - ($prevSnapshot->stability_index ?? 0));

        if ($entropyDelta > $threshold || $stabilityDelta > $threshold) {
            Log::debug("NarrativeScheduler: Significant state delta detected (E: {$entropyDelta}, S: {$stabilityDelta}). Running pulse.");
            return true;
        }

        // 4. Probabilistic run for continuity (e.g., 5% chance instead of 10%)
        if (rand(1, 100) <= 5) {
            Log::debug("NarrativeScheduler: Periodic probabilistic pulse.");
            return true;
        }

        return false;
    }
}

