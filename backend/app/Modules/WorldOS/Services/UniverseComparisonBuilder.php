<?php

declare(strict_types=1);

namespace App\Modules\WorldOS\Services;

use App\Modules\World\Models\Universe;
use App\Modules\WorldOS\Http\Resources\Support\WorldOsResourceSupport;

class UniverseComparisonBuilder
{
    public function buildPayload(Universe $universe): array
    {
        $latestSnapshot = $universe->latestSnapshot;

        return [
            'id' => $universe->id,
            'name' => $universe->name ?: "Universe {$universe->id}",
            'status' => WorldOsResourceSupport::normalizeUniverseStatus($universe->status),
            'forked_at_tick' => (int) ($universe->forked_at_tick ?? 0),
            'current_tick' => (int) ($universe->current_tick ?? 0),
            'snapshot_id' => $latestSnapshot?->id,
            'tick' => (int) ($latestSnapshot?->tick ?? $universe->current_tick ?? 0),
            'entropy' => (float) ($latestSnapshot?->entropy ?? $universe->entropy ?? 0),
            'stability_index' => (float) ($latestSnapshot?->stability_index ?? $universe->structural_coherence ?? 0),
            'metrics' => WorldOsResourceSupport::toMetricArray($latestSnapshot?->metrics),
        ];
    }

    public function buildComparison(Universe $universe, Universe $branch): array
    {
        $source = $this->buildPayload($universe);
        $target = $this->buildPayload($branch);

        return [
            'universe_id' => $universe->id,
            'branch_id' => $branch->id,
            'source' => $source,
            'branch' => $target,
            'tick_span' => (int) $target['tick'] - (int) $source['tick'],
            'deltas' => [
                'current_tick' => (int) $target['current_tick'] - (int) $source['current_tick'],
                'entropy' => round((float) $target['entropy'] - (float) $source['entropy'], 4),
                'stability_index' => round((float) $target['stability_index'] - (float) $source['stability_index'], 4),
            ],
            'metric_deltas' => WorldOsResourceSupport::numericMetricDeltas(
                $source['metrics'] ?? [],
                $target['metrics'] ?? [],
            ),
        ];
    }
}
