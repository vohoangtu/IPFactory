<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Simulation\Entities\UniverseEntity;
use App\Modules\Simulation\Models\UniverseSnapshot;
use Illuminate\Support\Facades\Log;

/**
 * AdaptivePulseScheduler: Determines if a narrative "tick" should trigger an LLM call.
 * Formerly known as NarrativeScheduler in Modules.
 */
class AdaptivePulseScheduler
{
    public function shouldPulse(UniverseEntity $universe, UniverseSnapshot $snapshot): bool
    {
        if (!empty($snapshot->metrics['major_events'] ?? [])) {
            Log::debug("AdaptivePulseScheduler: Major event detected. Forcing pulse.");
            return true;
        }

        $threshold = (float) config('worldos.narrative.delta_threshold', 0.05);
        $prevSnapshot = UniverseSnapshot::where('universe_id', $universe->id)
            ->where('tick', '<', $snapshot->tick)
            ->orderByDesc('tick')
            ->first();

        if (!$prevSnapshot) return true;

        $entropyDelta = abs(($universe->entropy ?? 0) - ($prevSnapshot->entropy ?? 0));
        $stabilityDelta = abs(($universe->stabilityIndex ?? 0) - ($prevSnapshot->stability_index ?? 0));

        if ($entropyDelta > $threshold || $stabilityDelta > $threshold) {
            Log::debug("AdaptivePulseScheduler: Significant state delta detected.");
            return true;
        }

        return rand(1, 100) <= 10;
    }
}

