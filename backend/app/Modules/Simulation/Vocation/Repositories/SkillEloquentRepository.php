<?php

namespace App\Modules\Simulation\Vocation\Repositories;

use App\Modules\World\Models\Skill as SkillModel;
use App\Modules\Simulation\Vocation\Contracts\SkillRepositoryInterface;
use App\Modules\Simulation\Vocation\Entities\SkillEntity;

class SkillEloquentRepository implements SkillRepositoryInterface
{
    public function findById(int $id): ?SkillEntity
    {
        $model = SkillModel::find($id);
        if (!$model) {
            return null;
        }
        return $this->toEntity($model);
    }

    public function getByVocation(int $vocationId): array
    {
        return SkillModel::where('vocation_id', $vocationId)
            ->get()
            ->map(fn($model) => $this->toEntity($model))
            ->toArray();
    }

    public function save(SkillEntity $entity): void
    {
        $model = SkillModel::findOrNew($entity->id);
        $model->id = $entity->id;
        $model->vocation_id = $entity->vocationId;
        $model->name = $entity->name;
        $model->element = $entity->element;
        $model->cost = $entity->cost;
        $model->rule_dsl = $entity->ruleDsl;
        $model->metadata = $entity->metadata;
        $model->save();
    }

    private function toEntity(SkillModel $model): SkillEntity
    {
        return new SkillEntity(
            id: (int)$model->id,
            vocationId: (int)$model->vocation_id,
            name: $model->name,
            element: $model->element ?? [],
            cost: $model->cost,
            ruleDsl: $model->rule_dsl,
            metadata: $model->metadata ?? []
        );
    }
}

