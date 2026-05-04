<?php

namespace App\Modules\Narrative\Repositories;

use App\Modules\Narrative\Contracts\ChronicleRepositoryInterface;
use App\Modules\Narrative\Entities\ChronicleEntity;
use App\Modules\Narrative\Models\Chronicle as ChronicleModel;

class ChronicleEloquentRepository implements ChronicleRepositoryInterface
{
    public function findById(int $id): ?ChronicleEntity
    {
        $model = ChronicleModel::find($id);
        return $model ? $this->mapToEntity($model) : null;
    }

    public function save(ChronicleEntity $entity): ChronicleEntity
    {
        $data = $entity->toArray();
        if ($entity->id) {
            $model = ChronicleModel::findOrFail($entity->id);
            $model->update($data);
        } else {
            $model = ChronicleModel::create($data);
        }

        return $this->mapToEntity($model);
    }

    public function findByUniverse(int $universeId, int $limit = 10): array
    {
        return ChronicleModel::where('universe_id', $universeId)
            ->orderByDesc('to_tick')
            ->limit($limit)
            ->get()
            ->map(fn($m) => $this->mapToEntity($m))
            ->all();
    }

    public function findUnprocessedForTicks(int $universeId, int $fromTick, int $toTick, int $limit = 100): array
    {
        return ChronicleModel::where('universe_id', $universeId)
            ->whereNull('content')
            ->whereNotNull('raw_payload')
            ->where(function ($q) use ($fromTick, $toTick) {
                $q->whereBetween('from_tick', [$fromTick, $toTick])
                    ->orWhereBetween('to_tick', [$fromTick, $toTick]);
            })
            ->limit($limit)
            ->get()
            ->map(fn($m) => $this->mapToEntity($m))
            ->all();
    }

    private function mapToEntity(ChronicleModel $model): ChronicleEntity
    {
        return ChronicleEntity::create($model->toArray());
    }
}
