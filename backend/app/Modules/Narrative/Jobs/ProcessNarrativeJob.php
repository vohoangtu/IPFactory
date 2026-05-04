<?php

namespace App\Modules\Narrative\Jobs;

use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Models\Civilization;
use App\Modules\World\Models\Era;
use App\Modules\Narrative\Models\Myth;
use App\Modules\Narrative\Models\NarrativeJob;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Intelligence\Models\LegendaryAgent;
use App\Modules\Narrative\Services\CivilizationChronicleEngine;
use App\Modules\Narrative\Services\EraNarrativeEngine;
use App\Modules\Narrative\Services\NarrativeEngine;
use App\Modules\Narrative\Services\MythologyEngine;
use App\Modules\Narrative\Services\ReligionGenerator;
use App\Modules\Narrative\Services\ReligionSeedDetector;
use App\Modules\Narrative\Services\CausalTrajectoryGenerator;
use App\Modules\Narrative\Services\LegendEngine;
use App\Modules\Narrative\Services\ChapterGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Process a single narrative_jobs row: check cache, call LLM via appropriate engine, write storage, update status.
 * All LLM calls go through this job on the narrative queue.
 */
class ProcessNarrativeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 2;

    public function __construct(
        public readonly int $narrativeJobId
    ) {
        $this->onQueue('narrative');
    }

    public function handle(NarrativeEngine $narrativeEngine, EraNarrativeEngine $eraNarrativeEngine, CivilizationChronicleEngine $civilizationChronicleEngine, MythologyEngine $mythologyEngine, ReligionGenerator $religionGenerator, ReligionSeedDetector $religionSeedDetector, CausalTrajectoryGenerator $causal_trajectoryGenerator, FuturePredictor $futurePredictor, LegendEngine $legendEngine, ChapterGenerator $chapterGenerator): void
    {
        $job = NarrativeJob::find($this->narrativeJobId);
        if (!$job) {
            Log::warning("ProcessNarrativeJob: NarrativeJob #{$this->narrativeJobId} not found.");
            return;
        }

        if ($job->status !== NarrativeJob::STATUS_PENDING) {
            Log::info("ProcessNarrativeJob: Job #{$job->id} already processed (status={$job->status}).");
            return;
        }

        $job->update(['status' => NarrativeJob::STATUS_PROCESSING]);

        try {
            match ($job->engine) {
                'event' => $this->runEventEngine($job, $narrativeEngine),
                'era' => $this->runEraEngine($job, $eraNarrativeEngine),
                'civilization' => $this->runCivilizationEngine($job, $civilizationChronicleEngine),
                'mythology' => $this->runMythologyEngine($job, $mythologyEngine),
                'religion' => $this->runReligionEngine($job, $religionGenerator, $religionSeedDetector),
                'causal_trajectory' => $this->runCausalTrajectoryEngine($job, $causal_trajectoryGenerator, $futurePredictor),
                'legend' => $this->runLegendEngine($job, $legendEngine),
                'chapter' => $this->runChapterEngine($job, $chapterGenerator),
                default => Log::warning("ProcessNarrativeJob: Unknown engine {$job->engine}, marking completed."),
            };
            $job->update(['status' => NarrativeJob::STATUS_COMPLETED]);
        } catch (\Throwable $e) {
            Log::error("ProcessNarrativeJob: Job #{$job->id} failed: " . $e->getMessage());
            $job->update(['status' => NarrativeJob::STATUS_FAILED]);
            throw $e;
        }
    }

    private function runEventEngine(NarrativeJob $job, NarrativeEngine $narrativeEngine): void
    {
        $payload = $job->payload;
        $chronicleIds = $payload['chronicle_ids'] ?? (isset($payload['chronicle_id']) ? [$payload['chronicle_id']] : []);

        if (empty($chronicleIds)) {
            return;
        }

        $chronicles = Chronicle::whereIn('id', $chronicleIds)
            ->whereNull('content')
            ->whereNotNull('raw_payload')
            ->get();

        if ($chronicles->isEmpty()) {
            return;
        }

        if ($chronicles->count() === 1) {
            $narrativeEngine->generateForChronicle($chronicles->first());
        } else {
            $narrativeEngine->generateBatched($chronicles, (int) ($payload['tick_window_size'] ?? 1));
        }
    }

    private function runEraEngine(NarrativeJob $job, EraNarrativeEngine $eraNarrativeEngine): void
    {
        $payload = $job->payload;
        $eraId = $payload['era_id'] ?? null;

        if ($eraId !== null) {
            $era = Era::find($eraId);
            if ($era) {
                $eraNarrativeEngine->generateForEra($era);
            }
            return;
        }

        $startTick = (int) ($payload['start_tick'] ?? 0);
        $endTick = (int) ($payload['end_tick'] ?? 0);
        if ($startTick >= $endTick) {
            return;
        }

        $era = Era::where('universe_id', $job->universe_id)
            ->where('start_tick', $startTick)
            ->where('end_tick', $endTick)
            ->first();

        if ($era) {
            $eraNarrativeEngine->generateForEra($era);
        }
    }

    private function runCivilizationEngine(NarrativeJob $job, CivilizationChronicleEngine $civilizationChronicleEngine): void
    {
        $civilizationId = (int) ($job->payload['civilization_id'] ?? 0);
        if ($civilizationId <= 0) {
            return;
        }
        $civilization = Civilization::find($civilizationId);
        if ($civilization) {
            $civilizationChronicleEngine->generateForCivilization($civilization);
        }
    }

    private function runMythologyEngine(NarrativeJob $job, MythologyEngine $mythologyEngine): void
    {
        $payload = $job->payload ?? [];
        $payload['universe_id'] = $job->universe_id;
        $mythologyEngine->generateFromPayload($payload);
    }

    private function runReligionEngine(NarrativeJob $job, ReligionGenerator $religionGenerator, ReligionSeedDetector $religionSeedDetector): void
    {
        $mythId = (int) ($job->payload['myth_id'] ?? 0);
        if ($mythId <= 0) {
            return;
        }
        $myth = Myth::find($mythId);
        if (!$myth || !$religionSeedDetector->isReligionSeed($myth)) {
            return;
        }
        $religionGenerator->generateFromMyth($myth);
    }

    private function runCausalTrajectoryEngine(NarrativeJob $job, CausalTrajectoryGenerator $causal_trajectoryGenerator, FuturePredictor $futurePredictor): void
    {
        $universe = Universe::find($job->universe_id);
        if (!$universe) {
            return;
        }
        $tick = (int) ($job->payload['tick'] ?? 0);
        $stateSummary = $job->payload['state_summary'] ?? null;
        if ($stateSummary === null) {
            $snapshot = UniverseSnapshot::where('universe_id', $universe->id)
                ->where('tick', '<=', $tick)
                ->orderByDesc('tick')
                ->first();
            $stateSummary = $snapshot ? $futurePredictor->summarizeFromSnapshot($snapshot) : "Tick {$tick}: unknown state.";
        }
        $causal_trajectoryGenerator->generateForUniverse($universe, $tick, $stateSummary);
    }

    private function runLegendEngine(NarrativeJob $job, LegendEngine $legendEngine): void
    {
        $payload = $job->payload ?? [];
        $legendaryAgentId = isset($payload['legendary_agent_id']) ? (int) $payload['legendary_agent_id'] : null;
        $actorId = isset($payload['actor_id']) ? (int) $payload['actor_id'] : null;

        if ($legendaryAgentId) {
            $agent = LegendaryAgent::find($legendaryAgentId);
            if ($agent) {
                $legendEngine->generateForLegendaryAgent($agent);
            }
            return;
        }
        if ($actorId) {
            $legendEngine->generateForActor($job->universe_id, $actorId);
        }
    }

    private function runChapterEngine(NarrativeJob $job, ChapterGenerator $chapterGenerator): void
    {
        $universe = Universe::find($job->universe_id);
        if (!$universe) return;

        $seriesId = $job->payload['series_id'] ?? null;
        $chapterGenerator->generateForUniverse($universe, $seriesId);
    }
}

