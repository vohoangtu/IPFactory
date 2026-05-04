<?php

namespace App\Modules\SocialGraph\Listeners;

use App\Modules\Simulation\Events\UniverseSimulationPulsed;
use App\Contracts\GraphProviderInterface;
use Illuminate\Support\Facades\Log;

class SyncToGraph
{
    public function __construct(
        protected GraphProviderInterface $graphProvider
    ) {}

    public function handle(UniverseSimulationPulsed $event): void
    {
        $universe = $event->universe;
        $snapshot = $event->snapshot;

        try {
            // Đồng bộ Snapshot mới vào Đồ thị (Virtual hoặc Neo4j). Snapshot ảo có id null.
            $this->graphProvider->sync($universe->id, [
                'snapshot_id' => $snapshot->exists ? $snapshot->id : null,
                'tick' => $snapshot->tick,
                'state' => $snapshot->state_vector ?? [],
            ]);

            Log::debug("SyncToGraph: Universe {$universe->id} synced at tick {$snapshot->tick}" . ($snapshot->exists ? " (snapshot_id={$snapshot->id})" : ' (virtual)'));
        } catch (\Throwable $e) {
            Log::error("SyncToGraph failed for Universe {$universe->id}: " . $e->getMessage());
        }
    }
}

