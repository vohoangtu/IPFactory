<?php

namespace App\Modules\Narrative\Repositories;

use App\Modules\Narrative\Contracts\ArtifactRepositoryInterface;
use App\Modules\Narrative\Entities\ArtifactEntity;
use App\Modules\Narrative\Models\Artifact as ArtifactModel;

class ArtifactEloquentRepository implements ArtifactRepositoryInterface
{
    public function findById(int $id): ?ArtifactEntity
    {
        $model = ArtifactModel::find($id);
        return $model ? $this->mapToEntity($model) : null;
    }

    public function save(ArtifactEntity $entity): ArtifactEntity
    {
        $data = $entity->toArray();
        if ($entity->id) {
            $model = ArtifactModel::findOrFail($entity->id);
            $model->update($data);
        } else {
            $model = ArtifactModel::create($data);
        }

        return $this->mapToEntity($model);
    }

    public function delete(int $id): bool
    {
        return (bool) ArtifactModel::destroy($id);
    }

    public function findByUniverse(int $universeId): array
    {
        return ArtifactModel::where('universe_id', $universeId)
            ->get()
            ->map(fn($m) => $this->mapToEntity($m))
            ->all();
    }

    private function mapToEntity(ArtifactModel $model): ArtifactEntity
    {
        return ArtifactEntity::create($model->toArray());
    }
}
