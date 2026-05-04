<?php

namespace App\Modules\Simulation\Listeners;

use App\Modules\Simulation\Events\EpochTransitioned;
use Illuminate\Support\Facades\Log;

class HandleEpochTransition
{
    /**
     * Handle the event.
     *
     * @param  EpochTransitioned  $event
     * @return void
     */
    public function handle(EpochTransitioned $event): void
    {
        $newEra = $event->newEpoch->name;
        $theme = $event->newEpoch->metadata['theme'] ?? 'unknown';
        
        Log::info("HandleEpochTransition: Era transition detected to [{$newEra}] with theme [{$theme}]");

        // Trigger historical artifact log for Agent pickup
        Log::info("HISTORICAL_ARTIFACT_REQUEST: Era=[{$newEra}], Theme=[{$theme}], Tick=[{$event->tick}]");
        
        // Multi-persona: In a real system, we might update a system_persona in the world state here.
        
        // Trigger Audio Sync (Phase 2: Auditory Singularity)
        \App\Modules\Simulation\Jobs\ComposeEpochSoundtrackJob::dispatch($newEra, $theme);
    }
}
