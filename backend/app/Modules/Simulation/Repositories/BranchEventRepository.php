<?php

namespace App\Modules\Simulation\Repositories;

use App\Modules\Simulation\Contracts\BranchEventRepositoryInterface;
use App\Modules\Simulation\Entities\BranchEventEntity;
use App\Modules\Simulation\Models\BranchEvent as BranchEventModel;

class BranchEventRepository implements BranchEventRepositoryInterface
{
    public function findById(int $id): ?BranchEventEntity
    {
        $model = BranchEventModel::find($id);
        return $model ? $this->mapToEntity($model) : null;
    }

    public function save(BranchEventEntity $entity): BranchEventEntity
    {
        $data = $entity->toArray();
        if ($entity->id) {
            $model = BranchEventModel::findOrFail($entity->id);
            $model->update($data);
        } else {
            $model = BranchEventModel::create($data);
        }

        return $this->mapToEntity($model);
    }

    public function existsFork(int $universeId, int $fromTick): bool
    {
        return BranchEventModel::where('universe_id', $universeId)
            ->where('from_tick', $fromTick)
            ->where('event_type', 'fork')
            ->exists();
    }

    public function hasForkAsParent(int $universeId): bool
    {
        return BranchEventModel::where('universe_id', $universeId)
            ->where('event_type', 'fork')
            ->exists();
    }

    private function mapToEntity(BranchEventModel $model): BranchEventEntity
    {
        return BranchEventEntity::create($model->toArray());
    }
}

