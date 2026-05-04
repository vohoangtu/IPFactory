<?php

declare(strict_types=1);

namespace App\Modules\WorldOS\Services;

use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Narrative\Models\Myth;
use App\Modules\World\Models\Religion;
use App\Modules\World\Models\Universe;
use App\Modules\WorldOS\Http\Resources\Support\WorldOsResourceSupport;
use App\Modules\Simulation\Core\Engines\Meta\HistoryEngine;
use App\Modules\Simulation\Services\Civilization\CultureIdentityProjector;
use App\Modules\Simulation\Services\Civilization\CivilizationDossierProjector;
use App\Modules\Simulation\Services\Civilization\MaterialIdentityProjector;

class UniverseDossierService
{
    public function __construct(
        protected CultureIdentityProjector $cultureIdentityProjector,
        protected MaterialIdentityProjector $materialIdentityProjector,
        protected HistoryEngine $historyEngine,
        protected CivilizationDossierProjector $civilizationDossierProjector
    ) {}

    public function getDossier(int $universeId): array
    {
        $universe = Universe::with('latestSnapshot')->findOrFail($universeId);
        $snapshot = $universe->latestSnapshot;
        $stateVector = is_array($snapshot?->state_vector) ? $snapshot->state_vector : [];
        $metrics = WorldOsResourceSupport::toMetricArray($snapshot?->metrics);

        $materialIdentity = $metrics['material_identity'] ?? $this->materialIdentityProjector->projectFromState($stateVector);
        $cultureIdentity = $this->cultureIdentityProjector->projectFromState($stateVector);
        $historySpine = $this->historyEngine->getHistoricalSpine($universe);
        $eraSummaries = $this->historyEngine->getEraSummaries($universe);
        $dominantReligion = Religion::query()
            ->where('universe_id', $universe->id)
            ->orderByDesc('followers')
            ->first();
        $civilizationProfile = $this->civilizationDossierProjector->project(
            $universe,
            $stateVector,
            $materialIdentity,
            $cultureIdentity,
            $dominantReligion,
        );

        return [
            'universe_id' => $universe->id,
            'name' => $universe->name ?: "Universe {$universe->id}",
            'tick' => (int) ($snapshot?->tick ?? $universe->current_tick ?? 0),
            'status' => WorldOsResourceSupport::normalizeUniverseStatus($universe->status),
            'material_identity' => $materialIdentity,
            'culture_identity' => $cultureIdentity,
            'civilization_profile' => $civilizationProfile,
            'civilization' => [
                'settlement_count' => count((array) data_get($stateVector, 'civilization.settlements', [])),
                'knowledge_node_count' => count((array) data_get($stateVector, 'civilization.knowledge_graph.nodes', [])),
                'discovery_fitness' => (float) data_get($stateVector, 'civilization.discovery.fitness', 0),
            ],
            'myths' => [
                'count' => Myth::query()->where('universe_id', $universe->id)->count(),
                'top_types' => Myth::query()
                    ->where('universe_id', $universe->id)
                    ->selectRaw('myth_type, COUNT(*) as total')
                    ->groupBy('myth_type')
                    ->orderByDesc('total')
                    ->limit(5)
                    ->get()
                    ->map(fn ($myth) => ['type' => $myth->myth_type, 'count' => (int) $myth->total])
                    ->values()
                    ->all(),
            ],
            'religions' => [
                'count' => Religion::query()->where('universe_id', $universe->id)->count(),
                'dominant' => $dominantReligion ? [
                    'id' => $dominantReligion->id,
                    'name' => $dominantReligion->name,
                    'followers' => (int) $dominantReligion->followers,
                    'spread_rate' => (float) $dominantReligion->spread_rate,
                    'doctrine' => $dominantReligion->doctrine,
                    'holy_sites' => $dominantReligion->holy_sites ?? [],
                ] : null,
            ],
            'history' => [
                'material_transition_count' => Chronicle::query()->where('universe_id', $universe->id)->where('type', 'material_transition')->count(),
                'narrative_tick_count' => Chronicle::query()->where('universe_id', $universe->id)->where('type', 'narrative_tick')->count(),
                'total_chronicle_count' => Chronicle::query()->where('universe_id', $universe->id)->count(),
                'spine' => $historySpine,
                'eras' => $eraSummaries,
            ],
        ];
    }
}
