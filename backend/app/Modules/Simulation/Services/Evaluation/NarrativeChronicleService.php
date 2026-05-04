<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Services\Evaluation;

use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Narrative\Models\HistoricalFact;
use App\Modules\Intelligence\Models\LegendaryAgent;
use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Services\AI\EpistemicService;
use App\Modules\Narrative\Services\CausalTrajectoryFulfillment;
use App\Modules\Narrative\Services\EraDetector;
use App\Modules\Narrative\Services\HistoricalFactEngine;
use App\Modules\Narrative\Services\NarrativeCompiler;
use App\Modules\Narrative\Services\NarrativeMemoryGraphService;
use App\Modules\Narrative\Services\NarrativeQueueManager;
use App\Modules\Narrative\Services\ReligionSpreadEngine;
use App\Modules\Simulation\Services\Core\AdaptiveSchedulerService;
use App\Modules\Simulation\Services\Core\EventNormalizer;
use Illuminate\Support\Facades\Log;

class NarrativeChronicleService
{
    public function __construct(
        protected EpistemicService $epistemicService,
        protected NarrativeCompiler $narrativeCompiler,
        protected NarrativeMemoryGraphService $narrativeMemoryGraph,
        protected NarrativeQueueManager $narrativeScheduler,
        protected EraDetector $eraDetector,
        protected ReligionSpreadEngine $religionSpreadEngine,
        protected CausalTrajectoryFulfillment $causal_trajectoryFulfillment,
        protected HistoricalFactEngine $historicalFactEngine,
        protected EventNormalizer $eventNormalizer,
        protected AdaptiveSchedulerService $adaptiveScheduler,
    ) {
    }

    public function createNarrativeChronicle($universe, $snapshot): void
    {
        $entropy = (float) $snapshot->entropy;
        $noise = $this->epistemicService->calculateNoise($universe, $entropy);

        // Narrative v2: fact-first — use Historical Fact for this tick when available
        $worldEventId = null;
        $historicalBlock = null;
        $fact = null;
        if (config('worldos.narrative_v2.enable_fact_first_chronicle', true)) {
            $fact = HistoricalFact::where('universe_id', $universe->id)
                ->where('tick', $snapshot->tick)
                ->latest()
                ->first();
            if ($fact !== null) {
                $worldEventId = $fact->world_event_id;
                $historicalBlock = [
                    'year' => $fact->year,
                    'tick' => $fact->tick,
                    'category' => $fact->category,
                    'metrics' => $fact->metrics_after ?? [],
                    'events' => $fact->facts ?? [],
                ];
            }
        }

        $interpretations = [];
        // Perspective layer disabled (PerspectiveEngine missing)

        // Distort snapshot data for AI perception
        $canonicalData = [
            'entropy' => $entropy,
            'stability_index' => (float) $snapshot->stability_index,
            'metrics' => $snapshot->metrics ?? [],
        ];
        $perceivedData = $this->epistemicService->distort($universe, $canonicalData, $noise);
        if ($historicalBlock !== null) {
            $perceivedData['historical_block'] = $historicalBlock;
        }

        // Compile mythic text (compiler uses historical_block in prompt when present)
        $narrative = $this->narrativeCompiler->setUniverse($universe)->compile($perceivedData, $noise);

        $rawPayload = [
            'action' => 'legacy_event',
            'description' => $narrative,
            'interpretations' => $interpretations,
        ];
        if ($historicalBlock !== null) {
            $rawPayload['historical_block'] = $historicalBlock;
        }

        $chronicle = Chronicle::create([
            'universe_id' => $universe->id,
            'parent_id' => $fact?->parent_id,
            'world_event_id' => $worldEventId,
            'from_tick' => $snapshot->tick,
            'to_tick' => $snapshot->tick,
            'type' => 'narrative',
            'raw_payload' => $rawPayload,
            'perceived_archive_snapshot' => [
                'noise_level' => $noise,
                'clarity' => $this->epistemicService->getClarityLabel($noise),
                'perceived_state' => $perceivedData,
            ],
        ]);

        if (config('worldos.narrative_v2.enable_memory_graph', true) && $fact !== null) {
            try {
                $this->narrativeMemoryGraph->linkChronicleToFact($chronicle, $fact);
            } catch (\Throwable $e) {
                Log::warning('NarrativeMemoryGraph linkChronicleToFact failed: '.$e->getMessage());
            }
        }

        // Schedule LLM narrative via queue (no sync LLM call)
        $this->narrativeScheduler->scheduleEventForChronicle($universe->id, $chronicle->id);

        // Narrative 4-tier + Belief loop: interval-based jobs (era, religion spread, causal_trajectory, legend)
        $this->runNarrativeIntervals($universe, $snapshot);
    }

    /**
     * Run narrative interval jobs: era (every era_interval), religion spread, causal_trajectory, legend.
     */
    public function runNarrativeIntervals(Universe $universe, $snapshot): void
    {
        $tick = (int) $snapshot->tick;
        $eraInterval = (int) config('worldos.narrative.era_interval', 200);
        $mythologyInterval = (int) config('worldos.narrative.mythology_interval', 50);
        $religionInterval = (int) config('worldos.narrative.religion_interval', 200);
        $causal_trajectoryInterval = (int) config('worldos.narrative.causal_trajectory_interval', 500);
        $legendInterval = (int) config('worldos.narrative.legend_interval', 100);
        $chapterInterval = (int) config('worldos.narrative.chapter_interval', 150);

        if ($tick > 0 && $eraInterval > 0 && $this->adaptiveScheduler->shouldRun('era_detect', $universe, $snapshot)) {
            try {
                $startTick = max(0, $tick - $eraInterval);
                $era = $this->eraDetector->detectAndCreate($universe, $startTick, $tick);
                if ($era !== null) {
                    $this->narrativeScheduler->scheduleEra($universe->id, $startTick, $tick, $era->id);
                }
            } catch (\Throwable $e) {
                Log::warning('Narrative interval: Era detect/schedule failed: '.$e->getMessage());
            }
        }

        if ($mythologyInterval > 0 && $this->adaptiveScheduler->shouldRun('mythology', $universe, $snapshot)) {
            try {
                $startTick = max(0, $tick - $mythologyInterval);
                $chronicleIds = Chronicle::query()
                    ->where('universe_id', $universe->id)
                    ->whereBetween('to_tick', [$startTick, $tick])
                    ->whereIn('type', ['narrative', 'material_transition', 'war', 'collapse', 'crisis'])
                    ->orderByDesc('importance')
                    ->limit(8)
                    ->pluck('id')
                    ->all();

                $this->narrativeScheduler->scheduleMythology($universe->id, [
                    'start_tick' => $startTick,
                    'end_tick' => $tick,
                    'chronicle_ids' => $chronicleIds,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Narrative interval: Mythology failed: '.$e->getMessage());
            }
        }

        if ($religionInterval > 0 && $this->adaptiveScheduler->shouldRun('religion_spread', $universe, $snapshot)) {
            try {
                $this->religionSpreadEngine->runForUniverse($universe, $tick);
            } catch (\Throwable $e) {
                Log::warning('Narrative interval: Religion spread failed: '.$e->getMessage());
            }
        }

        if ($causal_trajectoryInterval > 0 && $this->adaptiveScheduler->shouldRun('causal_trajectory', $universe, $snapshot)) {
            try {
                $this->narrativeScheduler->scheduleCausalTrajectory($universe->id, $tick);
                $this->causal_trajectoryFulfillment->evaluateForUniverse($universe->id, $tick);
            } catch (\Throwable $e) {
                Log::warning('Narrative interval: CausalTrajectory failed: '.$e->getMessage());
            }
        }

        if ($legendInterval > 0 && $this->adaptiveScheduler->shouldRun('legend', $universe, $snapshot)) {
            try {
                $agent = LegendaryAgent::where('universe_id', $universe->id)->inRandomOrder()->first();
                if ($agent !== null) {
                    $this->narrativeScheduler->scheduleLegend($universe->id, null, $agent->id);
                }
            } catch (\Throwable $e) {
                Log::warning('Narrative interval: Legend failed: '.$e->getMessage());
            }
        }

        if ($chapterInterval > 0 && $this->adaptiveScheduler->shouldRun('chapter', $universe, $snapshot)) {
            try {
                $this->narrativeScheduler->scheduleChapter($universe->id);
            } catch (\Throwable $e) {
                Log::warning('Narrative interval: Chapter schedule failed: '.$e->getMessage());
            }
        }
    }

    /**
     * Proxy to AdaptiveSchedulerService to check if a module should run this tick.
     */
    public function shouldRun(string $module, $universe, $snapshot): bool
    {
        return $this->adaptiveScheduler->shouldRun($module, $universe, $snapshot);
    }

    /**
     * Process world events: normalize tick summary → record historical fact → publish event.
     */
    public function processWorldEvents($universe, $snapshot, array $decisionData = [], array $scars = []): void
    {
        $worldEvent = $this->eventNormalizer->buildTickSummaryEvent($universe, $snapshot, $decisionData, $scars);

        if ($worldEvent !== null) {
            try {
                $this->historicalFactEngine->record($worldEvent, $snapshot);
            } catch (\Throwable $e) {
                Log::warning('HistoricalFactEngine record failed: '.$e->getMessage());
            }
        }
    }
}
