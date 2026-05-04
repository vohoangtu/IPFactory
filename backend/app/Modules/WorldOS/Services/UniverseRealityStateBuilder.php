<?php

declare(strict_types=1);

namespace App\Modules\WorldOS\Services;

use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Services\Civilization\CultureIdentityProjector;
use App\Modules\World\Models\Universe;
use App\Modules\WorldOS\Http\Resources\Support\WorldOsResourceSupport;

class UniverseRealityStateBuilder
{
    public function __construct(
        protected CultureIdentityProjector $cultureProjector,
    ) {}

    public function build(Universe $universe, array $materials): array
    {
        $snapshot = $universe->latestSnapshot;
        $stateVector = $this->resolveStateVector($universe, $snapshot);
        $worldState = WorldState::fromArray($stateVector);
        $tick = (int) ($snapshot?->tick ?? $universe->current_tick ?? 0);

        return [
            'universe_id' => $universe->id,
            'tick' => $tick,
            'era' => $universe->world->civilization_era ?? 'genesis',
            'pulse' => $this->buildPulse($universe, $snapshot, $stateVector),
            'layers' => [
                'physical' => $worldState->getPhysicalLayer(),
                'life' => $worldState->getLifeLayer(),
                'social' => $worldState->getSocialLayer(),
                'narrative' => $worldState->getNarrativeLayer(),
            ],
            'materials' => $materials,
            'civilization' => $this->buildCivilization($stateVector, $snapshot),
            'vfx_config' => WorldOsResourceSupport::getVfxConfigForEra($universe->world->civilization_era),
        ];
    }

    private function resolveStateVector(Universe $universe, ?object $snapshot): array
    {
        if ($snapshot) {
            $raw = is_array($snapshot->state_vector) ? $snapshot->state_vector : (json_decode($snapshot->state_vector, true) ?? []);

            return is_array($raw) ? $raw : [];
        }

        $raw = is_array($universe->state_vector) ? $universe->state_vector : (json_decode($universe->state_vector, true) ?? []);

        return is_array($raw) ? $raw : [];
    }

    private function buildPulse(Universe $universe, ?object $snapshot, array $stateVector): array
    {
        return [
            'entropy' => (float) ($snapshot?->entropy ?? $universe->entropy ?? 0),
            'stability_index' => (float) ($snapshot?->stability_index ?? $universe->structural_coherence ?? 0),
            'entropy_threshold' => 1.0,
            'collapse_probability' => (float) ($stateVector['collapse_probability'] ?? 0),
        ];
    }

    private function buildCivilization(array $stateVector, ?object $snapshot): array
    {
        return [
            'complexity' => (float) (data_get($stateVector, 'civilization.discovery.fitness', 0)),
            'knowledge_nodes' => count(data_get($stateVector, 'civilization.knowledge_graph.nodes', [])),
            'settlements' => data_get($stateVector, 'civilization.settlements', []),
            'material_identity' => $snapshot?->metrics['material_identity'] ?? [],
            'culture_identity' => $this->cultureProjector->projectFromState($stateVector),
        ];
    }
}
