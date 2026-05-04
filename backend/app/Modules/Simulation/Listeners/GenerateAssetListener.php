<?php

namespace App\Modules\Simulation\Listeners;

use App\Modules\SocialGraph\Events\CelebrityEmerged;
use App\Modules\World\Events\ArtifactDiscovered;
use App\Jobs\GenerateVisualAssetJob;

class GenerateAssetListener
{
    /**
     * Handle the CelebrityEmerged event.
     */
    public function handleCelebrity(CelebrityEmerged $event): void
    {
        $prompt = "A cinematic portrait of a {$event->vocation} with fame level {$event->fame} in the WorldOS simulation zone {$event->zoneId}.";
        dispatch(new GenerateVisualAssetJob($event->universeId, 'celebrity', $prompt, $event->agentId));
    }

    /**
     * Handle the ArtifactDiscovered event.
     */
    public function handleArtifact(ArtifactDiscovered $event): void
    {
        $prompt = "A blueprint diagram of a mysterious artifact found in zone {$event->zoneId} with mass {$event->mass} and high knowledge encoded.";
        dispatch(new GenerateVisualAssetJob($event->universeId, 'artifact', $prompt, $event->artifactId));
    }
}
