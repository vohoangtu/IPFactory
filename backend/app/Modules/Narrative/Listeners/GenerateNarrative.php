<?php

namespace App\Modules\Narrative\Listeners;

use App\Modules\Simulation\Events\UniverseSimulationPulsed;
use App\Modules\Narrative\Contracts\ChronicleRepositoryInterface;
use App\Modules\Narrative\Services\NarrativeScheduler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class GenerateNarrative implements ShouldQueue
{
    public function __construct(
        protected \App\Modules\Narrative\Services\NarrativeQueueManager $narrativeScheduler,
        protected ChronicleRepositoryInterface $chronicleRepository
    ) {}

    public function handle(UniverseSimulationPulsed $event): void
    {
        $universe = $event->universe;
        $snapshot = $event->snapshot;

        $fromTick = (int) $universe->current_tick;
        $toTick = (int) $snapshot->tick;
        $ticks = (int) ($event->engineResponse['_ticks'] ?? 1);
        if ($fromTick >= $toTick && $ticks > 0) {
            $fromTick = max(0, $toTick - $ticks);
        }

        if ($toTick <= $fromTick) {
            return;
        }

        $chronicleIds = $this->chronicleRepository->findUnprocessedForTicks(
            $universe->id,
            $fromTick,
            $toTick,
            100
        );

        $ids = array_map(fn($e) => $e->id, $chronicleIds);

        if (!empty($ids)) {
            try {
                $this->narrativeScheduler->scheduleEvent($universe->id, $ids, 1);
            } catch (\Throwable $e) {
                Log::error("GenerateNarrative: schedule event failed: " . $e->getMessage());
            }
        }
    }
}


