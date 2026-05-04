<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Narrative\Jobs\ProcessNarrativeJob;
use App\Modules\Narrative\Models\NarrativeJob;
use App\Modules\Narrative\Services\AdaptivePulseScheduler;
use App\Modules\Simulation\Entities\UniverseEntity;
use App\Modules\Simulation\Models\UniverseSnapshot;

/**
 * NarrativeQueueManager: Schedulers queue/LLM tasks (event/era/civilization/legend/...).
 * Formerly known as NarrativeScheduler in app/Services.
 */
class NarrativeQueueManager
{
    public function __construct(
        protected AdaptivePulseScheduler $adaptiveScheduler,
    ) {}

    public function shouldPulse(UniverseEntity $universe, UniverseSnapshot $snapshot): bool
    {
        return $this->adaptiveScheduler->shouldPulse($universe, $snapshot);
    }

    public function scheduleEvent(int $universeId, array $chronicleIds, int $tickWindowSize = 1): ?NarrativeJob
    {
        $chronicleIds = array_values(array_unique(array_map('intval', $chronicleIds)));
        if (empty($chronicleIds)) return null;

        return $this->dispatchJob($universeId, 'event', [
            'chronicle_ids' => $chronicleIds,
            'tick_window_size' => max(1, (int) $tickWindowSize),
        ]);
    }

    public function scheduleEventForChronicle(int $universeId, int $chronicleId): ?NarrativeJob
    {
        return $this->scheduleEvent($universeId, [$chronicleId], 1);
    }

    public function scheduleEra(int $universeId, int $startTick, int $endTick, ?int $eraId = null): ?NarrativeJob
    {
        $payload = $eraId !== null ? ['era_id' => (int) $eraId] : ['start_tick' => (int) $startTick, 'end_tick' => (int) $endTick];
        return $this->dispatchJob($universeId, 'era', $payload);
    }

    public function scheduleCivilization(int $universeId, int $civilizationId): ?NarrativeJob
    {
        return $this->dispatchJob($universeId, 'civilization', ['civilization_id' => (int) $civilizationId]);
    }

    public function scheduleMythology(int $universeId, array $payload): ?NarrativeJob
    {
        $normalized = array_filter([
            'chronicle_ids' => !empty($payload['chronicle_ids']) ? array_values(array_unique(array_map('intval', (array) $payload['chronicle_ids']))) : null,
            'start_tick' => isset($payload['start_tick']) ? (int) $payload['start_tick'] : null,
            'end_tick' => isset($payload['end_tick']) ? (int) $payload['end_tick'] : null,
            'myth_type' => isset($payload['myth_type']) ? (string) $payload['myth_type'] : null,
        ], static fn ($value) => $value !== null && $value !== []);

        if ($normalized === []) {
            return null;
        }

        return $this->dispatchJob($universeId, 'mythology', $normalized);
    }

    public function scheduleReligion(int $universeId, int $mythId): ?NarrativeJob
    {
        if ($mythId <= 0) {
            return null;
        }

        return $this->dispatchJob($universeId, 'religion', ['myth_id' => (int) $mythId]);
    }

    public function scheduleCausalTrajectory(int $universeId, int $tick, ?string $stateSummary = null): ?NarrativeJob
    {
        $payload = ['tick' => (int) $tick];
        if ($stateSummary) $payload['state_summary'] = $stateSummary;
        return $this->dispatchJob($universeId, 'causal_trajectory', $payload);
    }

    public function scheduleLegend(int $universeId, ?int $actorId = null, ?int $legendaryAgentId = null): ?NarrativeJob
    {
        $payload = [];
        if ($legendaryAgentId) $payload['legendary_agent_id'] = (int) $legendaryAgentId;
        if ($actorId) $payload['actor_id'] = (int) $actorId;
        if (empty($payload)) return null;
        return $this->dispatchJob($universeId, 'legend', $payload);
    }

    public function scheduleChapter(int $universeId, ?int $seriesId = null): ?NarrativeJob
    {
        return $this->dispatchJob($universeId, 'chapter', ['series_id' => $seriesId]);
    }

    protected function dispatchJob(int $universeId, string $engine, array $payload): NarrativeJob
    {
        $job = NarrativeJob::create([
            'universe_id' => $universeId,
            'engine' => $engine,
            'payload' => $payload,
            'status' => NarrativeJob::STATUS_PENDING,
        ]);
        ProcessNarrativeJob::dispatch($job->id);
        return $job;
    }
}

