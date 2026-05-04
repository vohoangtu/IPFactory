<?php

namespace App\Modules\Simulation\Services\Society;

use App\Contracts\Repositories\UniverseRepositoryInterface;
use App\Modules\Intelligence\Models\Actor;
use App\Modules\Institutions\Models\InstitutionalEntity;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

/**
 * Doc §17: Legitimacy aggregate and elite overproduction from institutions.
 * Reads InstitutionalEntity (legitimacy, members, founders); computes legitimacy_aggregate and elite_ratio.
 * Writes state_vector.civilization.politics.legitimacy_aggregate, elite_ratio (or merges into politics).
 */
class LegitimacyEliteService
{
    public function __construct(
        protected UniverseRepositoryInterface $universeRepository
    ) {}

    public function runWithState(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, int $currentTick): void
    {
        $interval = (int) config('worldos.intelligence.politics_tick_interval', 25);
        if ($interval <= 0 || $currentTick % $interval !== 0) {
            return;
        }

        $institutions = $state->getInstitutionalEntities();
        $legitimacyAggregate = 0.0;
        $eliteCount = 0;

        if (count($institutions) > 0) {
            $legitimacySum = 0.0;
            $founderIds = [];
            $memberSum = 0;

            foreach ($institutions as $inst) {
                $legitimacySum += (float) $inst->legitimacy;
                if ($inst->founder_actor_id) {
                    $founderIds[$inst->founder_actor_id] = true;
                }
                $memberSum += (int) $inst->members;
            }

            $legitimacyAggregate = $legitimacySum / count($institutions);
            $eliteCount = count($founderIds) + (int) min($memberSum * 0.2, 50);
        }

        $actors = $state->getActorEntities();
        $aliveCount = 0;
        foreach ($actors as $actor) {
            if ($actor->isAlive) $aliveCount++;
        }

        $eliteRatio = $aliveCount > 0 ? min(1.0, $eliteCount / $aliveCount) : 0.0;
        $eliteOverproduction = (float) config('worldos.legitimacy.elite_overproduction_threshold', 0.15);
        $overproduction = $eliteRatio > $eliteOverproduction ? round($eliteRatio - $eliteOverproduction, 4) : 0.0;

        $politics = $state->get('civilization.politics', []);
        $politics['legitimacy_aggregate'] = round(max(0, min(1, $legitimacyAggregate)), 4);
        $politics['elite_ratio'] = round($eliteRatio, 4);
        $politics['elite_overproduction'] = $overproduction;
        $politics['updated_tick'] = $currentTick;

        $state->set('civilization.politics', $politics);
        \Illuminate\Support\Facades\Log::debug("LegitimacyEliteService: Legitimacy/Elite updated via manifold at tick {$currentTick}");
    }

    public function evaluate(Universe $universe, int $currentTick): void
    {
        // Deprecated
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


