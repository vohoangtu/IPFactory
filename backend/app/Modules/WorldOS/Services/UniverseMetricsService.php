<?php

declare(strict_types=1);

namespace App\Modules\WorldOS\Services;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Narrative\Models\Myth;
use App\Modules\Narrative\Models\MythScar;
use App\Modules\World\Models\Religion;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\WorldOS\Http\Resources\Support\WorldOsResourceSupport;
use App\Modules\Simulation\Services\Civilization\CultureIdentityProjector;

class UniverseMetricsService
{
    public function __construct(
        protected CultureIdentityProjector $cultureIdentityProjector
    ) {}

    public function getMetrics(int $universeId): array
    {
        $universe = Universe::with('latestSnapshot')->withCount('childUniverses')->findOrFail($universeId);
        $latestSnapshot = $universe->latestSnapshot;
        $snapshotMetrics = WorldOsResourceSupport::toMetricArray($latestSnapshot?->metrics);
        $stateVector = is_array($latestSnapshot?->state_vector) ? $latestSnapshot->state_vector : [];
        $cultureIdentity = $this->cultureIdentityProjector->projectFromState($stateVector);

        return [
            'universe_id' => $universe->id,
            'status' => WorldOsResourceSupport::normalizeUniverseStatus($universe->status),
            'current_tick' => (int) ($universe->current_tick ?? 0),
            'stability' => WorldOsResourceSupport::stabilityForUniverse($universe, $universe->latestSnapshot),
            'entropy' => (float) ($universe->entropy ?? $universe->latestSnapshot?->entropy ?? 0),
            'snapshot_count' => UniverseSnapshot::query()->where('universe_id', $universe->id)->count(),
            'branch_count' => (int) ($universe->child_universes_count ?? 0),
            'actor_count' => Actor::query()->where('universe_id', $universe->id)->count(),
            'chronicle_count' => Chronicle::query()->where('universe_id', $universe->id)->count(),
            'anomaly_count' => MythScar::query()->where('universe_id', $universe->id)->whereNull('resolved_at_tick')->count(),
            'myth_count' => Myth::query()->where('universe_id', $universe->id)->count(),
            'religion_count' => Religion::query()->where('universe_id', $universe->id)->count(),
            'material_identity' => $snapshotMetrics['material_identity'] ?? [],
            'culture_identity' => $cultureIdentity,
        ];
    }
}
