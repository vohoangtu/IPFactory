<?php

namespace App\Modules\Simulation\Repositories;

use App\Modules\Simulation\Models\CausalTrajectory as TrajectoryModel;
use App\Modules\Simulation\Contracts\TrajectoryRepositoryInterface;
use App\Modules\Simulation\Entities\TrajectoryEntity;
use Illuminate\Support\Collection;

class TrajectoryEloquentRepository implements TrajectoryRepositoryInterface
{
    public function save(TrajectoryEntity $trajectory): TrajectoryEntity
    {
        $model = TrajectoryModel::updateOrCreate(
            ['id' => $trajectory->id],
            [
                'universe_id' => $trajectory->universeId,
                'target_tick' => $trajectory->targetTick,
                'phenomenon_description' => $trajectory->phenomenonDescription,
                'probability' => $trajectory->probability,
                'convergence_type' => $trajectory->convergenceType,
                'is_fulfilled' => $trajectory->isFulfilled,
            ]
        );

        return $this->modelToEntity($model);
    }

    public function getLatestForUniverse(int $universeId, int $limit = 5): Collection
    {
        return TrajectoryModel::where('universe_id', $universeId)
            ->orderByDesc('target_tick')
            ->limit($limit)
            ->get()
            ->map(fn($model) => $this->modelToEntity($model));
    }

    private function modelToEntity(TrajectoryModel $model): TrajectoryEntity
    {
        return new TrajectoryEntity(
            id: $model->id,
            universeId: $model->universe_id,
            targetTick: $model->target_tick,
            phenomenonDescription: $model->phenomenon_description,
            probability: (float) $model->probability,
            convergenceType: $model->convergence_type,
            isFulfilled: (boolean) $model->is_fulfilled
        );
    }
}

