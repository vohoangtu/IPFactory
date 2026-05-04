<?php

namespace App\Modules\Narrative\Repositories;

use App\Modules\Narrative\Contracts\MythScarRepositoryInterface;
use App\Modules\Narrative\Entities\MythScarEntity;
use App\Modules\Narrative\Models\MythScar as MythScarModel;

class MythScarEloquentRepository implements MythScarRepositoryInterface
{
    public function findById(int $id): ?MythScarEntity
    {
        $model = MythScarModel::find($id);
        return $model ? $this->mapToEntity($model) : null;
    }

    public function save(MythScarEntity $entity): MythScarEntity
    {
        $data = $entity->toArray();
        if ($entity->id) {
            $model = MythScarModel::findOrFail($entity->id);
            $model->update($data);
        } else {
            $model = MythScarModel::create($data);
        }

        return $this->mapToEntity($model);
    }

    public function countUnresolved(int $universeId): int
    {
        return MythScarModel::where('universe_id', $universeId)
            ->whereNull('resolved_at_tick')
            ->count();
    }

    public function findByUniverse(int $universeId): array
    {
        return MythScarModel::where('universe_id', $universeId)
            ->get()
            ->map(fn($m) => $this->mapToEntity($m))
            ->all();
    }

    private function mapToEntity(MythScarModel $model): MythScarEntity
    {
        return MythScarEntity::create($model->toArray());
    }
}
