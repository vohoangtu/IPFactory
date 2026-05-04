<?php

namespace App\Modules\Simulation\Listeners;

use App\Modules\Simulation\Events\UniverseSimulationPulsed;
use App\Modules\Narrative\Actions\ApplyMythScarAction;
use App\Modules\Simulation\Services\Core\EventNormalizer;
use App\Modules\Narrative\Services\HistoricalFactEngine;
use App\Modules\Simulation\Core\Contracts\WorldEventBusInterface;
use App\Modules\Intelligence\Services\AI\EpistemicService;
use App\Modules\Narrative\Services\NarrativeCompiler;
use App\Modules\Narrative\Services\NarrativeMemoryGraphService;
use App\Modules\Narrative\Services\NarrativeQueueManager;
use App\Modules\Narrative\Services\EraDetector;
use App\Modules\Narrative\Services\ReligionSpreadEngine;
use App\Modules\Narrative\Services\CausalTrajectoryFulfillment;
use Illuminate\Support\Facades\Log;

/**
 * SynchronizeNarrativeHistory — Phân rã từ EvaluateSimulationResult.
 * Chịu trách nhiệm về Historical Facts, Chronicles, Myth Scars và Narrative Intervals.
 */
class SynchronizeNarrativeHistory
{
    public function __construct(
        protected ApplyMythScarAction $applyMythScarAction,
        protected EventNormalizer $eventNormalizer,
        protected HistoricalFactEngine $historicalFactEngine,
        protected WorldEventBusInterface $worldEventBus,
        protected EpistemicService $epistemicService,
        protected NarrativeCompiler $narrativeCompiler,
        protected NarrativeMemoryGraphService $narrativeMemoryGraph,
        protected NarrativeQueueManager $narrativeScheduler,
        protected EraDetector $eraDetector,
        protected ReligionSpreadEngine $religionSpreadEngine,
        protected CausalTrajectoryFulfillment $causal_trajectoryFulfillment,
    ) {}

    public function handle(UniverseSimulationPulsed $event): void
    {
        $universe = $event->universe;
        $snapshot = $event->snapshot;
        $decisionData = $event->engineResponse['decision'] ?? []; // Assuming we pass this or re-calculate

        try {
            // 1. Myth Scars
            $this->applyMythScarAction->execute($universe, $snapshot, $decisionData);

            // 2. World Events & Historical Facts
            if (config('worldos\narrative_v2\enable_world_event', true)) {
                $worldEvent = $this->eventNormalizer->buildTickSummaryEvent(
                    $universe,
                    $snapshot,
                    $decisionData,
                    $event->engineResponse['scars'] ?? []
                );
                if ($worldEvent) {
                    $this->historicalFactEngine->record($worldEvent, $snapshot);
                    $this->worldEventBus->publish($worldEvent);
                }
            }

            // 3. Narrative Chronicle
            $this->createNarrativeChronicle($universe, $snapshot);

            // 4. Narrative Intervals (Era, Religion, etc.)
            $this->runNarrativeIntervals($universe, $snapshot);

        } catch (\Throwable $e) {
            Log::error("SynchronizeNarrativeHistory failed: " . $e->getMessage(), [
                'universe_id' => $universe->id,
                'tick' => $snapshot->tick
            ]);
        }
    }

    protected function createNarrativeChronicle($universe, $snapshot): void
    {
        $entropy = (float) $snapshot->entropy;
        $noise = $this->epistemicService->calculateNoise($universe, $entropy);

        $fact = \App\Modules\Narrative\Models\HistoricalFact::where('universe_id', $universe->id)
            ->where('tick', $snapshot->tick)
            ->latest()
            ->first();

        $canonicalData = [
            'entropy' => $entropy,
            'stability_index' => (float) $snapshot->stability_index,
            'metrics' => $snapshot->metrics ?? [],
        ];
        
        $perceivedData = $this->epistemicService->distort($canonicalData, $noise);
        if ($fact) {
            $perceivedData['historical_block'] = [
                'year' => $fact->year,
                'tick' => $fact->tick,
                'category' => $fact->category,
                'metrics' => $fact->metrics_after ?? [],
                'events' => $fact->facts ?? [],
            ];
        }

        $narrative = $this->narrativeCompiler->setUniverse($universe)->compile($perceivedData, $noise);

        $chronicle = \App\Modules\Narrative\Models\Chronicle::create([
            'universe_id' => $universe->id,
            'parent_id' => $fact?->parent_id,
            'world_event_id' => $fact?->world_event_id,
            'from_tick' => $snapshot->tick,
            'to_tick' => $snapshot->tick,
            'type' => 'narrative',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => $narrative,
                'historical_block' => $perceivedData['historical_block'] ?? null,
            ],
            'perceived_archive_snapshot' => [
                'noise_level' => $noise,
                'clarity' => $this->epistemicService->getClarityLabel($noise),
                'perceived_state' => $perceivedData,
            ],
        ]);

        if (config('worldos\narrative_v2\enable_memory_graph', true) && $fact) {
            $this->narrativeMemoryGraph->linkChronicleToFact($chronicle, $fact);
        }

        $this->narrativeScheduler->scheduleEventForChronicle($universe->id, $chronicle->id);
    }

    protected function runNarrativeIntervals($universe, $snapshot): void
    {
        $tick = (int) $snapshot->tick;
        $eraInterval = (int) config('worldos\narrative\era_interval', 200);

        if ($tick > 0 && $eraInterval > 0) {
            $startTick = max(0, $tick - $eraInterval);
            $era = $this->eraDetector->detectAndCreate($universe, $startTick, $tick);
            if ($era) {
                $this->narrativeScheduler->scheduleEra($universe->id, $startTick, $tick, $era->id);
            }
        }

        $this->religionSpreadEngine->runForUniverse($universe, $tick);
        $this->causal_trajectoryFulfillment->evaluateForUniverse($universe->id, $tick);
    }
}
