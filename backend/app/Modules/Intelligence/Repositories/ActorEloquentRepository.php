<?php

namespace App\Modules\Intelligence\Repositories;

use App\Modules\Intelligence\Models\Actor as ActorModel;
use App\Modules\Intelligence\Contracts\ActorRepositoryInterface;
use App\Modules\Intelligence\Entities\ActorEntity;

class ActorEloquentRepository implements ActorRepositoryInterface
{
    public function findById(int $id): ?ActorEntity
    {
        $model = ActorModel::find($id);
        return $model ? $this->mapToEntity($model) : null;
    }

    public function findByUniverse(int $universeId): array
    {
        return ActorModel::where('universe_id', $universeId)
            ->get()
            ->map(fn($model) => $this->mapToEntity($model))
            ->toArray();
    }

    public function findActiveByUniverse(int $universeId): array
    {
        return ActorModel::where('universe_id', $universeId)
            ->where('is_alive', true)
            ->get()
            ->map(fn($model) => $this->mapToEntity($model))
            ->toArray();
    }

    public function save(ActorEntity $entity): void
    {
        $this->saveBatch([$entity]);
    }

    public function saveBatch(array $entities): void
    {
        if (empty($entities)) {
            return;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($entities) {
            foreach ($entities as $entity) {
                $data = [
                    'universe_id' => $entity->universeId,
                    'name' => $entity->name,
                    'archetype' => $entity->archetype,
                    'traits' => $entity->traits,
                    'metrics' => $entity->metrics,
                    'is_alive' => $entity->isAlive,
                    'generation' => $entity->generation,
                    'biography' => $entity->biography,
                    'is_heroic' => $entity->isHeroic,
                    'heroic_type' => $entity->heroicType,
                ];

                if ($entity->id) {
                    // JSON encoding for array fields as we are using raw update/query builder for performance
                    $data['traits'] = json_encode($data['traits']);
                    $data['metrics'] = json_encode($data['metrics']);
                    
                    ActorModel::where('id', $entity->id)->update($data);
                } else {
                    ActorModel::create($data);
                }
            }
        });
    }

    public function delete(int $id): void
    {
        ActorModel::destroy($id);
    }

    public function deleteBatch(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        ActorModel::whereIn('id', $ids)->delete();
    }

    public function getActiveCount(int $universeId): int
    {
        return ActorModel::where('universe_id', $universeId)
            ->where('is_alive', true)
            ->count();
    }

    private function mapToEntity(ActorModel $model): ActorEntity
    {
        return new ActorEntity(
            id: $model->id,
            universeId: $model->universe_id,
            name: $model->name,
            archetype: $model->archetype,
            traits: $model->traits ?? [],
            metrics: $model->metrics ?? [],
            isAlive: (bool) $model->is_alive,
            generation: (int) $model->generation,
            biography: $model->biography,
            isHeroic: (bool) ($model->is_heroic ?? false),
            heroicType: $model->heroic_type ?? null,
            vocationId: $model->vocation_id
        );
    }
}

