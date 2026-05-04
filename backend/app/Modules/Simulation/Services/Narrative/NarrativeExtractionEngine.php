<?php

namespace App\Modules\Simulation\Services\Narrative;

use App\Modules\Narrative\Models\Chronicle;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Narrative Extraction Engine (Phase D): turns timeline (universe history) into story/lore.
 * Uses TimelineSelectionEngine to pick best timelines, then NarrativeAiService to generate
 * consolidated lore chronicles. No legacy code; clean integration.
 */
class NarrativeExtractionEngine
{
    public function __construct(
        protected TimelineSelectionEngine $timelineSelection,
        protected \App\Modules\Narrative\Services\UniverseHistoryGenerator $historyGenerator
    ) {}

    /**
     * Extract a single lore/story chronicle for one universe over a tick range.
     */
    public function extractLore(Universe $universe, ?int $fromTick = null, ?int $toTick = null): ?Chronicle
    {
        $fromTick = $fromTick ?? 0;
        if ($toTick === null) {
            $latest = $universe->snapshots()->orderByDesc('tick')->first();
            $toTick = $latest ? (int) $latest->tick : (int) ($universe->current_tick ?? 0);
        }

        try {
            $history = $this->historyGenerator->generate($universe, $fromTick, $toTick);
            
            if (!$history) {
                return null;
            }

            // Return a temporary chronicle or use the history text
            return Chronicle::create([
                'universe_id' => $universe->id,
                'from_tick' => $fromTick,
                'to_tick' => $toTick,
                'content' => $history->full_text,
                'type' => 'lore'
            ]);
        } catch (\Throwable $e) {
            Log::error("NarrativeExtractionEngine: extractLore failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Select best timelines for the world, then extract lore for each.
     * Returns collection of Chronicles (nulls filtered out).
     */
    public function extractBestFromWorld(World $world, ?int $limit = null): Collection
    {
        $limit = $limit ?? (int) config('worldos.narrative_extraction.default_limit', 5);
        $universes = $this->timelineSelection->selectBest($world, $limit);

        return $this->extractLoreForUniverses($universes);
    }


    /**
     * @param  Collection<int, Universe>  $universes
     * @return Collection<int, Chronicle>
     */
    protected function extractLoreForUniverses(Collection $universes): Collection
    {
        $chronicles = collect();
        foreach ($universes as $universe) {
            $chronicle = $this->extractLore($universe);
            if ($chronicle !== null) {
                $chronicles->push($chronicle);
            }
        }
        return $chronicles;
    }
}

