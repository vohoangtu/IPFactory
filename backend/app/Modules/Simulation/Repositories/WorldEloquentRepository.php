<?php

namespace App\Modules\Simulation\Repositories;

use App\Modules\World\Models\World as WorldModel;
use App\Modules\Simulation\Contracts\WorldRepositoryInterface;
use App\Modules\Simulation\Entities\WorldEntity;

/**
 * WorldEloquentRepository — Eloquent implementation của WorldRepositoryInterface.
 * 
 * Đây là lớp duy nhất trong Domain được phép giao tiếp trực tiếp với Eloquent.
 */
class WorldEloquentRepository implements WorldRepositoryInterface
{
    public function findById(int $id): ?WorldEntity
    {
        $model = WorldModel::find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findBySlug(string $slug): ?WorldEntity
    {
        $model = WorldModel::where('slug', $slug)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function save(WorldEntity $entity): void
    {
        $model = WorldModel::findOrFail($entity->id);

        $model->update([
            'axiom'                => $entity->axiom,
            'world_seed'           => $entity->worldSeed,
            'global_tick'          => $entity->globalTick,
            'current_genre'        => $entity->currentGenre,
            'base_genre'           => $entity->baseGenre,
            'active_genre_weights' => $entity->activeGenreWeights,
            'is_autonomic'         => $entity->isAutonomic,
            'is_chaotic'           => $entity->isChaotic,
            'snapshot_interval'    => $entity->snapshotInterval,
        ]);
    }

    public function firstOrCreate(string $name, array $attributes = []): WorldEntity
    {
        $model = WorldModel::firstOrCreate(
            ['name' => $name],
            array_merge([
                'slug'           => \Illuminate\Support\Str::slug($name),
                'axiom'          => [],
                'world_seed'     => [],
                'global_tick'    => 0,
                'is_autonomic'   => false,
                'is_chaotic'     => false,
            ], $attributes)
        );

        return $this->toEntity($model);
    }

    /**
     * Map Eloquent Model → Domain Entity.
     */
    private function toEntity(WorldModel $model): WorldEntity
    {
        return new WorldEntity(
            id: $model->id,
            multiverseId: $model->multiverse_id,
            name: $model->name ?? "World #{$model->id}",
            axiom: $model->axiom ?? [],
            worldSeed: $model->world_seed ?? [],
            globalTick: (int) ($model->global_tick ?? 0),
            currentGenre: $model->current_genre,
            baseGenre: $model->base_genre,
            activeGenreWeights: $model->active_genre_weights ?? [],
            isAutonomic: (bool) ($model->is_autonomic ?? false),
            isChaotic: (bool) ($model->is_chaotic ?? false),
            snapshotInterval: (int) ($model->snapshot_interval ?? 100),
        );
    }
}

