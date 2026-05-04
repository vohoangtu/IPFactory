<?php

namespace App\Modules\Simulation\Services\Core;

use App\Contracts\Repositories\UniverseRepositoryInterface;
use App\Modules\Institutions\Models\SupremeEntity;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

/**
 * Doc §11: Great Person Legacy — aggregate karma/power_level from SupremeEntity into state_vector.
 * Writes state_vector.great_person_legacy for narrative/dashboard.
 */
final class GreatPersonLegacyService
{
    public function __construct(
        protected UniverseRepositoryInterface $universeRepository
    ) {}

    public function writeToStateVector(Universe $universe, int $currentTick): void
    {
        $entities = SupremeEntity::where('universe_id', $universe->id)
            ->whereNull('fallen_at_tick')
            ->get();
        $count = $entities->count();
        $avgPower = $count > 0 ? (float) $entities->avg('power_level') : 0.0;
        $avgKarma = $count > 0 ? (float) $entities->avg('karma') : 0.5;
        $legacyStages = \App\Modules\Intelligence\Models\Actor::where('universe_id', $universe->id)
            ->whereIn('hero_stage', ['legacy', 'myth'])
            ->count();

        $stateVector = $this->getStateVector($universe);
        $stateVector['great_person_legacy'] = [
            'supreme_entity_count' => $count,
            'aggregate_power_level' => round($avgPower, 4),
            'aggregate_karma' => round($avgKarma, 4),
            'legacy_myth_actor_count' => $legacyStages,
            'updated_tick' => $currentTick,
        ];
        $this->universeRepository->update($universe->id, ['state_vector' => $stateVector]);
        Log::debug("GreatPersonLegacyService: Universe {$universe->id} great_person_legacy updated at tick {$currentTick}");
    }

    private function getStateVector(Universe $universe): array
    {
        $sv = $universe->state_vector;
        if (is_string($sv)) {
            $sv = json_decode($sv, true) ?? [];
        }
        return is_array($sv) ? $sv : [];
    }
}


