<?php

namespace App\Modules\Institutions\Repositories;

use App\Modules\Institutions\Models\SocialContract as SocialContractModel;
use App\Modules\Institutions\Contracts\SocialContractRepositoryInterface;
use App\Modules\Institutions\Entities\SocialContractEntity;

class SocialContractEloquentRepository implements SocialContractRepositoryInterface
{
    public function findByUniverse(int $universeId): array
    {
        return SocialContractModel::where('universe_id', $universeId)
            ->get()
            ->map(fn($model) => $this->mapToEntity($model))
            ->toArray();
    }

    public function save(SocialContractEntity $entity): void
    {
        $data = [
            'universe_id' => $entity->universeId,
            'type' => $entity->type,
            'participants' => $entity->participants,
            'strictness' => $entity->strictness,
            'duration' => $entity->duration,
            'created_at_tick' => $entity->createdAtTick,
            'expires_at_tick' => $entity->expiresAtTick,
            'institutional_entity_id' => $entity->institutionalEntityId,
        ];

        if ($entity->id) {
            SocialContractModel::where('id', $entity->id)->update($data);
        } else {
            SocialContractModel::create($data);
        }
    }

    public function delete(int $id): void
    {
        SocialContractModel::destroy($id);
    }

    private function mapToEntity(SocialContractModel $model): SocialContractEntity
    {
        return new SocialContractEntity(
            id: $model->id,
            universeId: $model->universe_id,
            type: $model->type,
            participants: $model->participants ?? [],
            strictness: (float) $model->strictness,
            duration: (int) $model->duration,
            createdAtTick: (int) $model->created_at_tick,
            expiresAtTick: $model->expires_at_tick,
            institutionalEntityId: $model->institutional_entity_id
        );
    }
}

