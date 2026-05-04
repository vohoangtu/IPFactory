<?php

namespace App\Modules\WorldOS\Http\Resources;

use App\Modules\World\Models\Universe;
use App\Modules\WorldOS\Http\Resources\Support\WorldOsResourceSupport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Universe */
class UniverseSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $latestSnapshot = $this->whenLoaded('latestSnapshot');
        $world = $this->whenLoaded('world');
        $worldName = $world?->name;

        return [
            'id' => $this->id,
            'world_id' => (int) $this->world_id,
            'name' => $this->name ?: ($worldName ? "{$worldName} Universe {$this->id}" : "Universe {$this->id}"),
            'status' => WorldOsResourceSupport::normalizeUniverseStatus($this->status),
            'current_tick' => (int) ($this->current_tick ?? 0),
            'era' => $this->epoch ?: 'Genesis',
            'focus' => $worldName ? "Observed branch of {$worldName}." : 'Observed universe branch.',
            'stability' => WorldOsResourceSupport::stabilityForUniverse($this->resource, $latestSnapshot),
            'structural_coherence' => (float) ($this->structural_coherence ?? 0),
            'entropy' => (float) ($this->entropy ?? $latestSnapshot?->entropy ?? 0),
            'informational_mass' => (float) ($latestSnapshot?->metrics['informational_mass'] ?? $latestSnapshot?->metrics['mass'] ?? 0),
            'branch_count' => (int) ($this->child_universes_count ?? $this->childUniverses?->count() ?? 0),
            'anomaly_count' => WorldOsResourceSupport::anomalyCount($this->resource),
            'world' => $world ? (new WorldResource($world))->resolve() : null,
            'latest_snapshot' => $latestSnapshot ? (new SnapshotResource($latestSnapshot))->resolve() : null,
        ];
    }
}
