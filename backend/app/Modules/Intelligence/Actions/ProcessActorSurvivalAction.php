<?php

namespace App\Modules\Intelligence\Actions;

use App\Modules\Intelligence\Contracts\ActorRepositoryInterface;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

class ProcessActorSurvivalAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        private ActorRepositoryInterface $actorRepository,
        private \App\Modules\Intelligence\Services\ActorTransitionSystem $transitionSystem,
        private \App\Modules\Intelligence\Services\EvolutionPressureService $evolutionPressure
    ) {}

    public function runWithState(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, array $simulationResponse): void
    {
        $actors = $state->getActorEntities();
        $universeId = (int) $state->get('universe_id');
        $seed = (int) $state->get('seed', 0);
        $snapshot = $simulationResponse['snapshot'] ?? null;
        $entropy = $snapshot !== null && isset($snapshot['entropy'])
            ? (float) $snapshot['entropy']
            : 0.5;
        $ticks = max(1, (int) ($simulationResponse['_ticks'] ?? 1));
        $snapshotTick = $snapshot !== null && isset($snapshot['tick'])
            ? (int) $snapshot['tick']
            : (int) ($state->get('tick', 0));

        $ticksPerYear = max(1, (int) config('worldos.intelligence.ticks_per_year', 1));
        $defaultMaxAgeYears = max(1, (int) config('worldos.intelligence.default_max_age_years', 150));

        if (count($actors) === 0) {
            Log::info("Intelligence: ProcessActorSurvivalAction skipped for Universe {$universeId} (tick {$snapshotTick}): no actors pooled.");
            return;
        }

        $pressure = $state->get('ecosystem.pressure', []);
        if (empty($pressure)) {
            $pressure = $this->evolutionPressure->fromUniverseId($universeId);
        }

        $deathCount = 0;
        $actorIndex = 0;

        $ecologicalCollapse = $state->get('ecological_collapse', []);
        $collapseActive = is_array($ecologicalCollapse) && !empty($ecologicalCollapse['active'])
            && $snapshotTick <= (int) ($ecologicalCollapse['until_tick'] ?? PHP_INT_MAX);
            
        $collapseDeathProbAdd = $collapseActive
            ? (float) config('worldos.intelligence.ecological_collapse_death_probability_add', 0.1)
            : 0.0;

        foreach ($actors as $actor) {
            if (!$actor->isAlive) {
                $actorIndex++;
                continue;
            }

            $oldState = $actor->isAlive;

            $actor->metrics = \App\Modules\Intelligence\Entities\ActorEntity::ensureLifeExpectancyInMetrics(
                $actor->metrics ?? [],
                $actor->traits ?? [],
                $defaultMaxAgeYears
            );
            $lifeExpectancy = (float) ($actor->metrics['life_expectancy'] ?? $defaultMaxAgeYears);

            $spawnedAtTick = isset($actor->metrics['spawned_at_tick']) ? (int) $actor->metrics['spawned_at_tick'] : 0;
            $ageTicks = max(0, $snapshotTick - $spawnedAtTick);
            $ageYears = $ageTicks / $ticksPerYear;

            if ($lifeExpectancy <= 0) $lifeExpectancy = (float) $defaultMaxAgeYears;
            
            if ($ageYears >= $lifeExpectancy) {
                $actor->isAlive = false;
                if ($oldState) {
                    $deathCount++;
                    Log::info("Intelligence: Actor {$actor->name} ({$actor->id}) perished in Universe {$universeId} at tick {$snapshotTick} (age {$ageYears} yrs >= life_expectancy {$lifeExpectancy}).");
                }
                $actorIndex++;
                continue;
            }

            $ageRatio = $lifeExpectancy > 0 ? ($ageYears / $lifeExpectancy) : 0.0;
            $fitness = $this->evolutionPressure->fitness($actor->traits ?? [], $actor->metrics['physic'] ?? null, $pressure);

            for ($t = 0; $t < $ticks && $actor->isAlive; $t++) {
                $tickForRng = $snapshotTick - $ticks + $t;
                $rngSalt = ($actor->id ?? 0) + ($actorIndex * 100000);
                $rng = new \App\Modules\Intelligence\Domain\Rng\SimulationRng($seed, $tickForRng, $rngSalt);
                $actorState = $actor->toState();
                $actorState = $this->transitionSystem->processSurvival($actorState, $entropy, $rng, $ageRatio, $fitness, $collapseDeathProbAdd);
                $actor->fromState($actorState);
            }

            if ($oldState && !$actor->isAlive) {
                $deathCount++;
                Log::info("Intelligence: Actor {$actor->name} ({$actor->id}) has perished in Universe {$universeId} at tick {$snapshotTick}.");
            }

            $actorIndex++;
        }

        if ($deathCount > 0) {
            Log::info("Intelligence: Processed survival for Universe {$universeId} via pool. Deaths: $deathCount.");
        }
    }

    public function handle(Universe $universe, array $simulationResponse): void
    {
        // Deprecated
    }
}


