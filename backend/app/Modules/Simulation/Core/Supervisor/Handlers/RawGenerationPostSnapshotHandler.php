<?php

namespace App\Modules\Simulation\Core\Supervisor\Handlers;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Supervisor\Contracts\PostSnapshotHandlerInterface;
use App\Modules\Narrative\Events\HistoricalEpochShifted;
use App\Modules\SocialGraph\Events\CelebrityEmerged;
use App\Modules\World\Events\ArtifactDiscovered;

class RawGenerationPostSnapshotHandler implements PostSnapshotHandlerInterface
{
    public function handle(Universe $universe, UniverseSnapshot $snapshot): void
    {
        $state = is_array($snapshot->state_vector) ? $snapshot->state_vector : (array) ($snapshot->state_vector ?? []);

        // 1. Process History Events
        if (!empty($state['pending_history_events'])) {
            foreach ($state['pending_history_events'] as $rawEvent) {
                HistoricalEpochShifted::dispatch(
                    (int) $universe->id,
                    (int) $snapshot->tick,
                    (int) $rawEvent['zone_id'],
                    (string) $rawEvent['event_type'],
                    (float) $rawEvent['impact_score'],
                    (array) $rawEvent['trigger_data']
                );
            }
        }

        // 2. Process Celebrities
        if (!empty($state['pending_celebrities'])) {
            foreach ($state['pending_celebrities'] as $rawVip) {
                CelebrityEmerged::dispatch(
                    (int) $universe->id,
                    (int) $snapshot->tick,
                    (int) $rawVip['zone_id'],
                    (int) $rawVip['id'],
                    (float) $rawVip['fame'],
                    (string) $rawVip['vocation']
                );
            }
        }

        // 3. Process Artifacts
        if (!empty($state['pending_artifacts'])) {
            foreach ($state['pending_artifacts'] as $rawArtifact) {
                ArtifactDiscovered::dispatch(
                    (int) $universe->id,
                    (int) $snapshot->tick,
                    (int) $rawArtifact['zone_id'],
                    (int) $rawArtifact['id'],
                    (float) $rawArtifact['mass'],
                    (float) $rawArtifact['knowledge_encoded']
                );
            }
        }
    }
}
