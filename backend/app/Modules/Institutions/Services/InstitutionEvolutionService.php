<?php

namespace App\Modules\Institutions\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Institutions\Contracts\InstitutionalRepositoryInterface;
use App\Modules\Institutions\Actions\SpawnInstitutionAction;
use App\Modules\Institutions\Actions\CollapseInstitutionAction;
use App\Modules\Intelligence\Contracts\ActorRepositoryInterface;
use App\Modules\Intelligence\Actions\SpawnActorAction;

class InstitutionEvolutionService
{
    public function __construct(
        private InstitutionalRepositoryInterface $institutionalRepository,
        private SpawnInstitutionAction $spawnAction,
        private CollapseInstitutionAction $collapseAction,
        private ActorRepositoryInterface $actorRepository,
        private SpawnActorAction $spawnActorAction,
        private \App\Modules\Institutions\Actions\DetectEmergentCivilizationsAction $detectEmergentCivsAction
    ) {}

    public function processPulse(Universe $universe, UniverseSnapshot $snapshot): void
    {
        $tick = (int) $snapshot->tick;
        $entities = $this->institutionalRepository->findActiveByUniverse($universe->id);
        $zones = ($universe->state_vector ?? [])['zones'] ?? [];
        $stability = (float) ($snapshot->stability_index ?? 1.0);
        $era = $universe->world->civilization_era ?? 'genesis';

        // 1. Evolution of existing entities
        foreach ($entities as $entity) {
            $entity->tick($zones);

            if ($entity->orgCapacity <= 0.5) {
                $this->collapseAction->handle($entity, $tick);
            } else {
                $this->institutionalRepository->save($entity);
            }
        }

        // 2. Detect New Civilizations (Clustering)
        $this->detectEmergentCivsAction->handle($universe, $snapshot);

        // 3. Potential spawning of other types (Cults, Rebels)
        $this->handlePotentialSpawning($universe, $tick, $zones, $era);

        // Crisis management
        if ($stability < config('worldos.institutions.stability_threshold', 0.4)) {
            $this->manageInstitutionalCrisis($universe, $entities, $tick, $era);
        }
    }

    protected function handlePotentialSpawning(Universe $universe, int $tick, array $zones, string $era): void
    {
        if (mt_rand(0, 10) > config('worldos.institutions.skip_probability', 3)) return;

        foreach ($zones as $zone) {
            $stress = (float) ($zone['state']['material_stress'] ?? ($zone['material_stress'] ?? 0));
            $culture = $zone['culture'] ?? [];

            if ($stress > config('worldos.institutions.stress_threshold', 0.8) && mt_rand(0, 5) === 0) {
                $this->spawnAction->doExecute($universe, $zone['id'], $tick, 'rebel', $era);
                return;
            }

            if (($culture['myth'] ?? 0) > 0.85 && mt_rand(0, 5) === 0) {
                $this->spawnAction->doExecute($universe, $zone['id'], $tick, 'cult', $era);
                return;
            }
        }
    }

    protected function manageInstitutionalCrisis(Universe $universe, array $entities, int $tick, string $era): void
    {
        if ($this->actorRepository->getActiveCount($universe->id) >= config('worldos.institutions.max_actors', 15)) return;

        foreach ($entities as $entity) {
            if ($entity->orgCapacity > config('worldos.institutions.org_capacity_threshold', 60) && mt_rand(0, 5) === 0) {
                $this->spawnInstitutionalLeader($universe, $entity, $tick, $era);
            }
        }
    }

    private function spawnInstitutionalLeader(Universe $universe, $entity, int $tick, string $era): void
    {
        $this->spawnActorAction->doExecute([
            'universe_id' => $universe->id,
            'name' => 'Lãnh đạo của ' . $entity->name,
            'archetype' => 'Leader',
            'era' => $era,
            'biography' => "Trỗi dậy để dẫn dắt {$entity->name} qua thời kỳ đen tối.",
            'metrics' => ['influence' => $entity->orgCapacity / 15.0]
        ]);
    }
}
