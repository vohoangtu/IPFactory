<?php

namespace App\Modules\Narrative\Repositories;

use App\Modules\Narrative\Contracts\DemiurgeRepositoryInterface;
use App\Modules\Narrative\Entities\DemiurgeEntity;
use App\Modules\World\Models\Demiurge as DemiurgeModel;

class DemiurgeEloquentRepository implements DemiurgeRepositoryInterface
{
    public function findById(int $id): ?DemiurgeEntity
    {
        $model = DemiurgeModel::find($id);
        return $model ? $this->mapToEntity($model) : null;
    }

    public function all(): array
    {
        return DemiurgeModel::all()->map(fn($m) => $this->mapToEntity($m))->all();
    }

    public function save(DemiurgeEntity $entity): DemiurgeEntity
    {
        $data = $entity->toArray();
        if ($entity->id) {
            $model = DemiurgeModel::findOrFail($entity->id);
            $model->update($data);
        } else {
            $model = DemiurgeModel::create($data);
        }

        return $this->mapToEntity($model);
    }

    public function decrementEssence(int $id, float $amount): void
    {
        DemiurgeModel::where('id', $id)->decrement('essence_pool', $amount);
    }

    private function mapToEntity(DemiurgeModel $model): DemiurgeEntity
    {
        return DemiurgeEntity::create($model->toArray());
    }
}
