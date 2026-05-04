<?php

namespace App\Modules\Institutions\Repositories;

use App\Modules\Institutions\Models\InstitutionalEntity as InstitutionalModel;
use App\Modules\Institutions\Contracts\InstitutionalRepositoryInterface;
use App\Modules\Institutions\Entities\InstitutionalEntity;

class InstitutionalEloquentRepository implements InstitutionalRepositoryInterface
{
    public function findById(int $id): ?InstitutionalEntity
    {
        $model = InstitutionalModel::find($id);
        return $model ? $this->mapToEntity($model) : null;
    }

    public function findActiveByUniverse(int $universeId): array
    {
        return InstitutionalModel::where('universe_id', $universeId)
            ->whereNull('collapsed_at_tick')
            ->get()
            ->map(fn($model) => $this->mapToEntity($model))
            ->toArray();
    }

    public function save(InstitutionalEntity $entity): void
    {
        $data = [
            'universe_id' => $entity->universeId,
            'name' => $entity->name,
            'entity_type' => $entity->entityType,
            'ideology_vector' => $entity->ideologyVector,
            'org_capacity' => $entity->orgCapacity,
            'influence_map' => $entity->influenceMap,
            'institutional_memory' => $entity->institutionalMemory,
            'legitimacy' => $entity->legitimacy,
            'spawned_at_tick' => $entity->spawnedAtTick,
            'collapsed_at_tick' => $entity->collapsedAtTick,
        ];

        if ($entity->id) {
            InstitutionalModel::where('id', $entity->id)->update($data);
        } else {
            InstitutionalModel::create($data);
        }
    }

    public function delete(int $id): void
    {
        InstitutionalModel::destroy($id);
    }

    private function mapToEntity(InstitutionalModel $model): InstitutionalEntity
    {
        return new InstitutionalEntity(
            id: $model->id,
            universeId: $model->universe_id,
            name: $model->name,
            entityType: $model->entity_type,
            ideologyVector: $model->ideology_vector ?? [],
            influenceMap: $model->influence_map ?? [],
            orgCapacity: (float) $model->org_capacity,
            institutionalMemory: (float) $model->institutional_memory,
            legitimacy: (float) $model->legitimacy,
            spawnedAtTick: $model->spawned_at_tick,
            collapsedAtTick: $model->collapsed_at_tick
        );
    }
}

