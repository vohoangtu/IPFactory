<?php

namespace App\Modules\Simulation\Vocation\Repositories;

use App\Modules\World\Models\VocationDefinition as VocationModel;
use App\Modules\Simulation\Vocation\Contracts\VocationRepositoryInterface;
use App\Modules\Simulation\Vocation\Entities\VocationEntity;

class VocationEloquentRepository implements VocationRepositoryInterface
{
    public function findById(string $id): ?VocationEntity
    {
        $model = VocationModel::find($id);
        if (!$model) {
            return null;
        }
        return $this->toEntity($model);
    }

    public function getAll(): array
    {
        return VocationModel::all()->map(fn($model) => $this->toEntity($model))->toArray();
    }

    public function save(VocationEntity $entity): void
    {
        $model = VocationModel::findOrNew($entity->id);
        $model->id = $entity->id;
        $model->name = $entity->name;
        $model->tier = $entity->tier;
        $model->element_affinity = $entity->elementAffinity;
        $model->requirements = $entity->requirements;
        $model->evolves_to = $entity->evolvesTo;
        $model->save();
    }

    private function toEntity(VocationModel $model): VocationEntity
    {
        return new VocationEntity(
            id: (string)$model->id,
            name: (string)$model->name,
            tier: (int)$model->tier,
            elementAffinity: $model->element_affinity ?? [],
            requirements: $model->requirements ?? [],
            evolvesTo: $model->evolves_to ? (string)$model->evolves_to : null,
            metadata: $model->metadata ?? []
        );
    }
}

