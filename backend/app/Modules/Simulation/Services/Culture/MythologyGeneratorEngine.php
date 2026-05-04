<?php

namespace App\Modules\Simulation\Services\Culture;

use App\Modules\Narrative\Models\Chronicle;
use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Services\MythologyEngine;
use App\Modules\Narrative\Services\NarrativeAiService;
use App\Modules\Narrative\Services\NarrativeQueueManager as NarrativeScheduler;
use Illuminate\Support\Facades\Log;

/**
 * Mythology Generator Engine (Phase F): turns timeline/chronicles into mythology-style narrative.
 * Produces Chronicle with type 'myth' for a universe over a tick range.
 * Can use MythologyEngine (myths table + queue) via generateViaQueue().
 */
class MythologyGeneratorEngine
{
    public function __construct(
        protected NarrativeAiService $narrativeAi,
        protected CivilizationMemoryEngine $civilizationMemory,
        protected ?MythologyEngine $mythologyEngine = null,
        protected ?NarrativeScheduler $narrativeScheduler = null
    ) {
        if ($this->mythologyEngine === null && app()->bound(MythologyEngine::class)) {
            $this->mythologyEngine = app(MythologyEngine::class);
        }
        if ($this->narrativeScheduler === null && app()->bound(NarrativeScheduler::class)) {
            $this->narrativeScheduler = app(NarrativeScheduler::class);
        }
    }

    /**
     * Schedule mythology narrative via queue (MythologyEngine → myths table). Use for async pipeline.
     */
    public function scheduleForRange(Universe $universe, int $fromTick, int $toTick, string $mythType = 'legend'): void
    {
        if ($this->narrativeScheduler) {
            $this->narrativeScheduler->scheduleMythology($universe->id, [
                'start_tick' => $fromTick,
                'end_tick' => $toTick,
                'myth_type' => $mythType,
            ]);
        }
    }

    /**
     * Generate a mythology chronicle for the universe over the given tick range.
     * Uses NarrativeAiService with type 'myth'; optional civilization memory enriches context.
     */
    public function generateFromUniverse(Universe $universe, ?int $fromTick = null, ?int $toTick = null): ?Chronicle
    {
        $fromTick = $fromTick ?? 0;
        if ($toTick === null) {
            $latest = $universe->snapshots()->orderByDesc('tick')->first();
            $toTick = $latest ? (int) $latest->tick : (int) ($universe->current_tick ?? 0);
        }

        if ($toTick < $fromTick) {
            Log::warning("MythologyGeneratorEngine: invalid range universe_id={$universe->id} from={$fromTick} to={$toTick}");
            return null;
        }

        $type = (string) config('worldos.mythology_generator.chronicle_type', 'myth');

        try {
            return $this->narrativeAi->generateChronicle($universe->id, $fromTick, $toTick, $type);
        } catch (\Throwable $e) {
            Log::error("MythologyGeneratorEngine: generateFromUniverse failed universe_id={$universe->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Return civilization memory for the universe in range (convenience for callers that need both).
     */
    public function getMemoryForUniverse(Universe $universe, ?int $fromTick = null, ?int $toTick = null): array
    {
        return $this->civilizationMemory->getMemory($universe, $fromTick, $toTick);
    }
}

