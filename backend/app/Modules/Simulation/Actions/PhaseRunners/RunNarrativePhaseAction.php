<?php
namespace App\Modules\Simulation\Actions\PhaseRunners;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Services\Core\EventNormalizer;
use App\Modules\Narrative\Services\HistoricalFactEngine;
use App\Modules\Simulation\Core\Contracts\WorldEventBusInterface;
use App\Modules\Intelligence\Services\AI\EpistemicService;
use App\Modules\Narrative\Services\NarrativeCompiler;
use App\Modules\Narrative\Services\NarrativeMemoryGraphService;
use App\Modules\Narrative\Services\NarrativeQueueManager;
use App\Modules\Simulation\Services\Core\AdaptiveSchedulerService;
use App\Modules\Narrative\Services\EraDetector;
use App\Modules\Narrative\Services\ReligionSpreadEngine;
use App\Modules\Narrative\Services\CausalTrajectoryFulfillment;
use Illuminate\Support\Facades\Log;

class RunNarrativePhaseAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected EventNormalizer $eventNormalizer,
        protected HistoricalFactEngine $historicalFactEngine,
        protected WorldEventBusInterface $worldEventBus,
        protected EpistemicService $epistemicService,
        protected NarrativeCompiler $narrativeCompiler,
        protected NarrativeMemoryGraphService $narrativeMemoryGraph,
        protected NarrativeQueueManager $narrativeScheduler,
        protected AdaptiveSchedulerService $adaptiveScheduler,
        protected EraDetector $eraDetector,
        protected ReligionSpreadEngine $religionSpreadEngine,
        protected CausalTrajectoryFulfillment $causal_trajectoryFulfillment
    ) {}

    public function execute(Universe $universe, UniverseSnapshot $snapshot, array $decisionData, array $engineResponse): void
    {
        // 1. WorldEvent + Historical Fact
        if (config('worldos.narrative_v2.enable_world_event', true)) {
            try {
                $worldEvent = $this->eventNormalizer->buildTickSummaryEvent(
                    $universe,
                    $snapshot,
                    $decisionData,
                    $engineResponse['scars'] ?? []
                );
                if ($worldEvent !== null) {
                    $this->historicalFactEngine->record($worldEvent, $snapshot);
                    $this->worldEventBus->publish($worldEvent);
                }

                foreach ($engineResponse['scars'] ?? [] as $scar) {
                    $scarEvent = $this->eventNormalizer->normalizeScarToEvent($universe, $scar);
                    $this->worldEventBus->publish($scarEvent);
                }
            } catch (\Throwable $e) {
                Log::warning('EventNormalizer/HistoricalFact failed: ' . $e->getMessage());
            }
        }

        // 2. AI Narrative (Epistemic Instability & Chronicle)
        try {
            $this->createNarrativeChronicle($universe, $snapshot);
        } catch (\Throwable $e) {
            Log::warning('Pulse: createNarrativeChronicle failed: ' . $e->getMessage());
        }

        // 3. Narrative intervals 
        try {
            $this->runNarrativeIntervals($universe, $snapshot);
        } catch (\Throwable $e) {
            Log::warning('Pulse: runNarrativeIntervals failed: ' . $e->getMessage());
        }
    }

    protected function createNarrativeChronicle($universe, $snapshot): void
    {
        $entropy = (float) $snapshot->entropy;
        $noise = $this->epistemicService->calculateNoise($universe, $entropy);

        $worldEventId = null;
        $historicalBlock = null;
        $fact = null;
        if (config('worldos.narrative_v2.enable_fact_first_chronicle', true)) {
            $fact = clone \App\Modules\Narrative\Models\HistoricalFact::where('universe_id', $universe->id)
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

        $canonicalData = [
            'entropy' => $entropy,
            'stability_index' => (float) $snapshot->stability_index,
            'metrics' => $snapshot->metrics ?? [],
        ];
        $perceivedData = $this->epistemicService->distort($canonicalData, $noise);
        if ($historicalBlock !== null) {
            $perceivedData['historical_block'] = $historicalBlock;
        }

        $narrative = $this->narrativeCompiler->setUniverse($universe)->compile($perceivedData, $noise);

        $rawPayload = [
            'action' => 'legacy_event',
            'description' => $narrative,
            'interpretations' => $interpretations,
        ];
        if ($historicalBlock !== null) {
            $rawPayload['historical_block'] = $historicalBlock;
        }

        $chronicle = \App\Modules\Narrative\Models\Chronicle::create([
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
                Log::warning('NarrativeMemoryGraph linkChronicleToFact failed: ' . $e->getMessage());
            }
        }

        $this->narrativeScheduler->scheduleEventForChronicle($universe->id, $chronicle->id);
    }

    protected function runNarrativeIntervals(\App\Modules\World\Models\Universe $universe, $snapshot): void
    {
        $tick = (int) $snapshot->tick;
        $eraInterval = (int) config('worldos.narrative.era_interval', 200);
        $mythologyInterval = (int) config('worldos.narrative.mythology_interval', 50);
        $religionInterval = (int) config('worldos.narrative.religion_interval', 200);
        $causal_trajectoryInterval = (int) config('worldos.narrative.causal_trajectory_interval', 500);
        $legendInterval = (int) config('worldos.narrative.legend_interval', 100);

        if ($tick > 0 && $eraInterval > 0 && $this->adaptiveScheduler->shouldRun('era_detect', $universe, $snapshot)) {
            try {
                $startTick = max(0, $tick - $eraInterval);
                $era = $this->eraDetector->detectAndCreate($universe, $startTick, $tick);
                if ($era !== null) {
                    $this->narrativeScheduler->scheduleEra($universe->id, $startTick, $tick, $era->id);
                }
            } catch (\Throwable $e) {
                Log::warning('Narrative interval: Era detect/schedule failed: ' . $e->getMessage());
            }
        }

        if ($mythologyInterval > 0 && $this->adaptiveScheduler->shouldRun('mythology', $universe, $snapshot)) {
            try {
                $startTick = max(0, $tick - $mythologyInterval);
                $chronicleIds = \App\Modules\Narrative\Models\Chronicle::query()
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
                Log::warning('Narrative interval: Mythology failed: ' . $e->getMessage());
            }
        }

        if ($religionInterval > 0 && $this->adaptiveScheduler->shouldRun('religion_spread', $universe, $snapshot)) {
            try {
                $this->religionSpreadEngine->runForUniverse($universe, $tick);
            } catch (\Throwable $e) {
                Log::warning('Narrative interval: Religion spread failed: ' . $e->getMessage());
            }
        }

        if ($causal_trajectoryInterval > 0 && $this->adaptiveScheduler->shouldRun('causal_trajectory', $universe, $snapshot)) {
            try {
                $this->narrativeScheduler->scheduleCausalTrajectory($universe->id, $tick);
                $this->causal_trajectoryFulfillment->evaluateForUniverse($universe->id, $tick);
            } catch (\Throwable $e) {
                Log::warning('Narrative interval: CausalTrajectory failed: ' . $e->getMessage());
            }
        }

        if ($legendInterval > 0 && $this->adaptiveScheduler->shouldRun('legend', $universe, $snapshot)) {
            try {
                $agent = \App\Modules\Intelligence\Models\LegendaryAgent::where('universe_id', $universe->id)->inRandomOrder()->first();
                if ($agent !== null) {
                    $this->narrativeScheduler->scheduleLegend($universe->id, null, $agent->id);
                }
            } catch (\Throwable $e) {
                Log::warning('Narrative interval: Legend failed: ' . $e->getMessage());
            }
        }
    }
}
