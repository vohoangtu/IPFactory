<?php

namespace App\Modules\Simulation\Core\EventBus;

use App\Modules\Simulation\Events\SimulationEventOccurred;
use App\Modules\Simulation\Core\Contracts\WorldEventBusBackendInterface;
use App\Modules\Simulation\Core\Events\WorldEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Default backend: persist to world_events table and dispatch SimulationEventOccurred.
 */
final class DatabaseWorldEventBusBackend implements WorldEventBusBackendInterface
{
    public function publish(WorldEvent $event): void
    {
        DB::table('world_events')->insert([
            'id' => $event->id,
            'universe_id' => $event->universeId,
            'tick' => $event->tick,
            'parent_id' => $event->parentId,
            'type' => $event->type,
            'payload' => json_encode($event->payload),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Event::dispatch(new SimulationEventOccurred(
            $event->universeId,
            $event->type,
            $event->tick,
            $event->toArray()
        ));
    }
}

