<?php

namespace App\Modules\Intelligence\Actions;

use App\Contracts\Repositories\UniverseRepositoryInterface;
use App\Modules\Intelligence\Contracts\ActorRepositoryInterface;
use App\Modules\Intelligence\Domain\Culture\CultureInheritanceService;
use App\Modules\Intelligence\Domain\Energy\EnergyMetricsResolver;
use App\Modules\Intelligence\Domain\Genome\GenomeMutator;
use App\Modules\Intelligence\Domain\Rng\SimulationRng;
use App\Modules\Intelligence\Entities\ActorEntity;
use App\Modules\Intelligence\Services\EvolutionPressureService;
use App\Modules\Simulation\Core\Engines\Biological\EcologicalPhaseTransitionEngine;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

/**
 * Energy Economy: consume metabolism, gather from zone, starvation, death when energy <= 0.
 * Reproduction (Phase 2b): spawn child with mutated genome when energy > cost.
 * Run after syncUniverseFromSnapshotData and before ProcessActorSurvivalAction.
 */
class ProcessActorEnergyAction implements \App\Contracts\ActionInterface
{
    public function __construct(
        private ActorRepositoryInterface $actorRepository,
        private UniverseRepositoryInterface $universeRepository,
        private SpawnActorAction $spawnActorAction,
        private EvolutionPressureService $evolutionPressure,
        private GenomeMutator $genomeMutator,
        private CultureInheritanceService $cultureInheritance,
        private EnergyMetricsResolver $energyResolver,
    ) {}

    public function runWithState(WorldState $state, array $simulationResponse): void
    {
        $actors = $state->getActorEntities();
        if (empty($actors)) {
            return;
        }

        $universeId = (int) $state->get('universe_id');
        $seed = (int) $state->get('seed', 0);
        $ticks = max(1, (int) ($simulationResponse['_ticks'] ?? 1));
        $snapshotTick = (int) (($simulationResponse['snapshot'] ?? [])['tick'] ?? $state->get('tick', 0));

        $config = $this->loadConfig();
        $zones = $state->get('zones', []);

        if (count($actors) < 30) {
            $actors = $this->spawnInitialAgents($state, $universeId, 30 - count($actors), $zones);
        }

        $pressure = $state->get('ecosystem.pressure', []);
        if (empty($pressure)) {
            $pressure = $this->evolutionPressure->fromUniverseId($universeId);
        }

        $collapseActive = $this->isCollapseActive($state, $snapshotTick);
        if ($collapseActive && $config['resourceRegenRate'] > 0) {
            $config['resourceRegenRate'] *= (float) config('worldos.intelligence.ecological_collapse_resource_regeneration_factor', 0.5);
        }

        $newActors = [];

        foreach ($actors as $actor) {
            if (!$actor->isAlive) {
                continue;
            }

            $this->bindZone($actor, $zones);
            $metrics = $this->energyResolver->resolve(
                $actor->metrics ?? [],
                $actor->traits ?? [],
                $actor->metrics['physic'] ?? null,
                $config['energyMaxDefault'],
                $config['metabolismBase']
            );

            $hunger = $this->updateHunger($actor, $zones, $ticks);
            $energy = $this->consumeEnergy($actor, $metrics, $hunger, $ticks, $config);

            if ($energy > $config['reproduceCost']) {
                $child = $this->attemptReproduction($actor, $energy, $metrics, $seed, $snapshotTick, $pressure, $collapseActive, $config, $universeId);
                if ($child !== null) {
                    $newActors[] = $child;
                    $energy -= $config['reproduceCost'];
                }
            }

            $metrics['energy'] = $energy;
            $metrics['starving'] = $energy < $config['starvationThreshold'];
            $metrics['species_id'] = $this->evolutionPressure->speciesId($actor->traits ?? [], $actor->metrics['physic'] ?? null);

            if ($energy <= 0) {
                $actor->isAlive = false;
                Log::info("Intelligence: Actor {$actor->name} ({$actor->id}) starved to death in Universe {$universeId}.");
            }

            $actor->metrics = $metrics;
            $actor->energy = $energy;
        }

        $this->regenerateResources($zones, $config['resourceRegenRate'], $ticks);
        $state->set('zones', $zones);

        if (!empty($newActors)) {
            $state->setActorEntities(array_merge($state->getActorEntities(), $newActors));
        }
    }

    private function loadConfig(): array
    {
        return [
            'metabolismBase' => (float) config('worldos.intelligence.metabolism_base', 0.5),
            'energyMaxDefault' => (float) config('worldos.intelligence.energy_max_default', 200),
            'starvationThreshold' => (float) config('worldos.intelligence.starvation_threshold', 20),
            'gatherRate' => (float) config('worldos.intelligence.gather_rate', 5),
            'resourceRegenRate' => (float) config('worldos.intelligence.resource_regen_rate', 2),
            'reproduceCost' => (float) config('worldos.intelligence.reproduce_cost', 80),
            'reproduceEnergyRatioChild' => (float) config('worldos.intelligence.reproduce_energy_ratio_child', 0.3),
            'mutationRate' => (float) config('worldos.intelligence.mutation_rate', 0.05),
        ];
    }

    private function isCollapseActive(WorldState $state, int $snapshotTick): bool
    {
        $ecologicalCollapse = $state->get('ecological_collapse', []);
        return is_array($ecologicalCollapse)
            && !empty($ecologicalCollapse['active'])
            && $snapshotTick <= (int) ($ecologicalCollapse['until_tick'] ?? PHP_INT_MAX);
    }

    private function bindZone(ActorEntity $actor, array $zones): void
    {
        if ($actor->zone_id === null && !empty($zones)) {
            $actor->zone_id = abs($actor->id ?? 0) % count($zones);
        }
    }

    private function updateHunger(ActorEntity $actor, array &$zones, int $ticks): float
    {
        $hunger = (float) ($actor->hunger ?? 0.5);
        $zoneStress = 0.0;

        if ($actor->zone_id !== null && !empty($zones)) {
            $zoneIndex = abs($actor->zone_id) % count($zones);
            $zoneStress = (float) ($zones[$zoneIndex]['state']['resource_stress'] ?? 0);
        }

        $hungerGrowthRate = 0.05 + ($zoneStress * 0.08);
        $hunger += $hungerGrowthRate * $ticks;

        if ($hunger > 0.6 && !empty($zones)) {
            $zoneIndex = $actor->zone_id % count($zones);
            $zone = &$zones[$zoneIndex];
            $foodKey = isset($zone['state']['food']) ? 'food' : (isset($zone['state']['resources']) ? 'resources' : 'resource');
            $available = (float) ($zone['state'][$foodKey] ?? 0);
            $searchAmount = 0.2 * $ticks;
            $gather = min($searchAmount, $available);

            if ($gather > 0) {
                $hunger = max(0, $hunger - ($gather * 2.0));
                $zone['state'][$foodKey] = max(0, $available - $gather);
            }
        }

        $actor->hunger = $hunger;
        return $hunger;
    }

    private function consumeEnergy(ActorEntity $actor, array $metrics, float $hunger, int $ticks, array $config): float
    {
        $metabolism = (float) ($metrics['metabolism'] ?? $config['metabolismBase']);
        $energy = (float) ($metrics['energy'] ?? $config['energyMaxDefault']);
        $maxEnergy = (float) ($metrics['max_energy'] ?? $config['energyMaxDefault']);

        if ($hunger > 0.8) {
            $energy -= $metabolism * 2.0 * $ticks * (0.9 + mt_rand(0, 200) / 1000);
        } else {
            $energy -= $metabolism * $ticks * (0.8 + mt_rand(0, 400) / 1000);
        }

        $energy = max(0, min($maxEnergy, $energy));
        $metrics['hunger'] = $hunger;
        $metrics['energy'] = $energy;
        $actor->metrics = $metrics;

        return $energy;
    }

    private function attemptReproduction(
        ActorEntity $actor,
        float $energy,
        array $metrics,
        int $seed,
        int $snapshotTick,
        array $pressure,
        bool $collapseActive,
        array $config,
        int $universeId
    ): ?ActorEntity {
        $longevity = (float) ($actor->traits[17] ?? $actor->traits['Longevity'] ?? 0.5);
        $fitness = $this->evolutionPressure->fitness($actor->traits ?? [], $actor->metrics['physic'] ?? null, $pressure);
        $reproduceProb = 0.08 * max(0, min(1, $longevity)) * $fitness;

        if ($collapseActive) {
            $reproduceProb *= (float) config('worldos.intelligence.ecological_collapse_reproduction_factor', 0.4);
        }

        $rng = new SimulationRng($seed, $snapshotTick, ($actor->id ?? 0) + 200000);
        if ($rng->nextFloat() >= $reproduceProb) {
            return null;
        }

        $childTraits = $this->genomeMutator->mutate(
            $actor->traits ?? [],
            $config['mutationRate'],
            new SimulationRng($seed, $snapshotTick, ($actor->id ?? 0) + 300000)
        );
        $childPhysic = $this->genomeMutator->mutate(
            $actor->metrics['physic'] ?? ActorEntity::defaultPhysicVector(),
            $config['mutationRate'],
            new SimulationRng($seed, $snapshotTick, ($actor->id ?? 0) + 400000)
        );
        $childEnergy = $energy * $config['reproduceEnergyRatioChild'];
        $childMetrics = [
            'physic' => $childPhysic,
            'spawned_at_tick' => $snapshotTick,
            'energy' => $childEnergy,
            'max_energy' => $metrics['max_energy'] ?? $config['energyMaxDefault'],
            'metabolism' => $metrics['metabolism'] ?? $config['metabolismBase'],
        ];

        $childCulture = $this->cultureInheritance->inherit(
            $actor->metrics['culture'] ?? null,
            $config['mutationRate'],
            new SimulationRng($seed, $snapshotTick, ($actor->id ?? 0) + 500000)
        );
        if ($childCulture !== null) {
            $childMetrics['culture'] = $childCulture;
        }

        $childEntity = $this->spawnActorAction->doExecute([
            'universe_id' => $universeId,
            'name' => $actor->name . ' Jr.',
            'archetype' => $actor->archetype,
            'traits' => $childTraits,
            'metrics' => $childMetrics,
            'generation' => ($actor->generation ?? 1) + 1,
        ]);

        Log::info("Intelligence: Actor {$actor->name} ({$actor->id}) reproduced in Universe {$universeId}, child {$childEntity->name}.");

        return $childEntity;
    }

    private function regenerateResources(array &$zones, float $resourceRegenRate, int $ticks): void
    {
        if (empty($zones) || $resourceRegenRate <= 0) {
            return;
        }
        foreach ($zones as &$zone) {
            if (!isset($zone['state'])) {
                $zone['state'] = [];
            }
            $foodKey = array_key_exists('food', $zone['state']) ? 'food' : 'resources';
            $current = (float) ($zone['state'][$foodKey] ?? 0);
            $biomeFactor = EcologicalPhaseTransitionEngine::resourceRegenFactorForZone($zone['state']);
            $zone['state'][$foodKey] = $current + $resourceRegenRate * $ticks * $biomeFactor;
        }
    }

    private function spawnInitialAgents(WorldState $state, int $universeId, int $count, array $zones): array
    {
        $actors = $state->getActorEntities();
        Log::info("Intelligence: Spawning $count initial agents for Universe $universeId");

        for ($i = 0; $i < $count; $i++) {
            $zoneId = !empty($zones) ? ($i % count($zones)) : null;
            $actor = $this->spawnActorAction->doExecute([
                'universe_id' => $universeId,
                'name' => 'Colonist ' . (count($actors) + 1),
                'archetype' => 'pioneer',
                'generation' => 1,
                'metrics' => [
                    'energy' => mt_rand(70, 100),
                    'hunger' => mt_rand(10, 30) / 100,
                    'zone_id' => $zoneId,
                ],
            ]);
            $actors[] = $actor;
        }

        $state->setActorEntities($actors);
        return $actors;
    }

    public function handle(Universe $universe, array $simulationResponse): void
    {
        // Deprecated
    }
}
