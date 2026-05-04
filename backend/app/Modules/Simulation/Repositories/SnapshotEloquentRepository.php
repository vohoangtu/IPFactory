<?php

namespace App\Modules\Simulation\Repositories;

use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Contracts\SnapshotRepositoryInterface;
use App\Modules\Simulation\Entities\SnapshotEntity;

/**
 * SnapshotEloquentRepository — Eloquent implementation cho SnapshotRepositoryInterface.
 */
class SnapshotEloquentRepository implements SnapshotRepositoryInterface
{
    public function findById(int $id): ?SnapshotEntity
    {
        $model = UniverseSnapshot::find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findLatestByUniverse(int $universeId): ?SnapshotEntity
    {
        $model = UniverseSnapshot::where('universe_id', $universeId)
            ->orderByDesc('tick')
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findByTick(int $universeId, int $tick): ?SnapshotEntity
    {
        $model = UniverseSnapshot::where('universe_id', $universeId)
            ->where('tick', $tick)
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function create(array $attributes): SnapshotEntity
    {
        return new SnapshotEntity(
            null,
            $attributes['universe_id'],
            $attributes['tick'],
            $attributes['state_vector'] ?? [],
            (float) ($attributes['entropy'] ?? 0.0),
            $attributes['metrics'] ?? [],
            $attributes['engine_manifest'] ?? null
        );
    }

    public function save(SnapshotEntity $snapshot): void
    {
        $model = $snapshot->id ? UniverseSnapshot::find($snapshot->id) : new UniverseSnapshot();
        if (!$model) {
            $model = new UniverseSnapshot();
        }
        
        $model->universe_id = $snapshot->universeId;
        $model->tick = $snapshot->tick;
        $model->state_vector = $snapshot->stateVector;
        $model->entropy = $snapshot->entropy;
        $model->metrics = $snapshot->metrics;
        $model->save();

        $snapshot->id = $model->id;
        $snapshot->createdAt = $model->created_at;
    }

    private function toEntity(UniverseSnapshot $model): SnapshotEntity
    {
        return new SnapshotEntity(
            $model->id,
            $model->universe_id,
            $model->tick,
            $model->state_vector ?? [],
            (float) $model->entropy,
            $model->metrics ?? [],
            $model->engine_manifest,
            $model->created_at
        );
    }
}

