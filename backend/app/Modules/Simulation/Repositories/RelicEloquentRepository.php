<?php

namespace App\Modules\Simulation\Repositories;

use App\Modules\World\Models\ExtradimensionalRelic;
use App\Modules\Simulation\Contracts\RelicRepositoryInterface;
use App\Modules\Simulation\Entities\RelicEntity;
use Illuminate\Support\Collection;

class RelicEloquentRepository implements RelicRepositoryInterface
{
    public function save(RelicEntity $relic): RelicEntity
    {
        $model = ExtradimensionalRelic::updateOrCreate(
            ['id' => $relic->id],
            [
                'world_id' => $relic->worldId,
                'origin_universe_id' => $relic->originUniverseId,
                'name' => $relic->name,
                'rarity' => $relic->rarity,
                'description' => $relic->description,
                'power_vector' => $relic->powerVector,
                'metadata' => $relic->metadata,
            ]
        );

        return $this->modelToEntity($model);
    }

    public function findById(int $id): ?RelicEntity
    {
        $model = ExtradimensionalRelic::find($id);
        return $model ? $this->modelToEntity($model) : null;
    }

    public function getForWorld(int $worldId): Collection
    {
        return ExtradimensionalRelic::where('world_id', $worldId)
            ->get()
            ->map(fn($model) => $this->modelToEntity($model));
    }

    private function modelToEntity(ExtradimensionalRelic $model): RelicEntity
    {
        return new RelicEntity(
            id: $model->id,
            worldId: $model->world_id,
            originUniverseId: $model->origin_universe_id,
            name: $model->name,
            rarity: $model->rarity,
            description: $model->description,
            powerVector: $model->power_vector,
            metadata: $model->metadata ?? []
        );
    }
}

