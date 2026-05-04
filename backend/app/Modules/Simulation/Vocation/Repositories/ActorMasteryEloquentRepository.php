<?php

namespace App\Modules\Simulation\Vocation\Repositories;

use App\Modules\Intelligence\Models\ActorMastery as MasteryModel;
use App\Modules\Simulation\Vocation\Contracts\ActorMasteryRepositoryInterface;
use App\Modules\Simulation\Vocation\Entities\ActorMasteryEntity;

class ActorMasteryEloquentRepository implements ActorMasteryRepositoryInterface
{
    public function findByActorAndVocation(string $actorId, string $vocationId): ?ActorMasteryEntity
    {
        $model = MasteryModel::where('actor_id', $actorId)
            ->where('vocation_id', $vocationId)
            ->first();
            
        if (!$model) {
            return null;
        }
        
        return $this->toEntity($model);
    }

    public function getByActor(string $actorId): array
    {
        return MasteryModel::where('actor_id', $actorId)
            ->get()
            ->map(fn($model) => $this->toEntity($model))
            ->toArray();
    }

    public function save(ActorMasteryEntity $entity): void
    {
        $model = MasteryModel::firstOrNew([
            'actor_id' => $entity->actorId,
            'vocation_id' => $entity->vocationId,
        ]);
        
        $model->level = $entity->level;
        $model->experience = $entity->experience;
        $model->save();
    }

    private function toEntity(MasteryModel $model): ActorMasteryEntity
    {
        return new ActorMasteryEntity(
            actorId: (int)$model->actor_id,
            vocationId: (string)$model->vocation_id,
            level: (int)$model->level,
            experience: (float)$model->experience
        );
    }
}

