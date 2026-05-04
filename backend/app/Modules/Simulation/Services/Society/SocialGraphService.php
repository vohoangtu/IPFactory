<?php

namespace App\Modules\Simulation\Services\Society;

use App\Contracts\Repositories\UniverseRepositoryInterface;
use App\Modules\Institutions\Models\InstitutionalEntity;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

/**
 * Doc §22: Social graph — trust, loyalty, rivalry between actors.
 * Derives edges from institutional membership (same idea/institution → trust/loyalty).
 * Writes state_vector['social_graph'] = { trust: [], loyalty: [], rivalry: [] }.
 */
class SocialGraphService
{
    public function __construct(
        protected UniverseRepositoryInterface $universeRepository
    ) {}

    public function evaluate(Universe $universe, int $currentTick): void
    {
        if (config('worldos.simulation.rust_authoritative', false)) {
            $stateVector = $this->getStateVector($universe);
            if (isset($stateVector['social_graph'])) {
                return;
            }
        }

        $stateVector = $this->getStateVector($universe);
        $institutions = InstitutionalEntity::where('universe_id', $universe->id)
            ->whereNull('collapsed_at_tick')
            ->whereNotNull('founder_actor_id')
            ->get();

        $trust = [];
        $loyalty = [];
        $rivalry = [];

        // Same idea_id → trust between founders; same entity → loyalty
        $byIdea = $institutions->groupBy('idea_id');
        foreach ($byIdea as $ideaId => $group) {
            $founders = $group->pluck('founder_actor_id')->unique()->filter()->values()->all();
            $n = count($founders);
            for ($i = 0; $i < $n; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    $a = (int) $founders[$i];
                    $b = (int) $founders[$j];
                    $trust[] = [$a, $b, round(0.5 + 0.3 * min($group->count() / 5, 1.0), 4)];
                    $loyalty[] = [$a, $b, round(0.4, 4)];
                }
            }
        }

        // Different entity_type (e.g. church vs state) with same universe → weak rivalry placeholder
        $entityTypes = $institutions->pluck('entity_type')->unique()->filter()->values()->all();
        if (count($entityTypes) >= 2) {
            $first = $institutions->where('entity_type', $entityTypes[0])->first();
            $second = $institutions->where('entity_type', $entityTypes[1])->first();
            if ($first && $second && $first->founder_actor_id && $second->founder_actor_id) {
                $rivalry[] = [(int) $first->founder_actor_id, (int) $second->founder_actor_id, 0.2];
            }
        }

        $stateVector['social_graph'] = [
            'trust'   => array_slice($trust, 0, (int) config('worldos.social_graph.max_trust_edges', 100)),
            'loyalty' => array_slice($loyalty, 0, (int) config('worldos.social_graph.max_loyalty_edges', 100)),
            'rivalry' => array_slice($rivalry, 0, (int) config('worldos.social_graph.max_rivalry_edges', 50)),
            'updated_tick' => $currentTick,
        ];
        $this->universeRepository->update($universe->id, ['state_vector' => $stateVector]);
        Log::debug("SocialGraphService: Universe {$universe->id} social_graph updated at tick {$currentTick}");
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

