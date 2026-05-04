<?php

namespace App\Modules\Simulation\Repositories;

use App\Modules\World\Models\Universe as UniverseModel;
use App\Modules\Simulation\Contracts\UniverseRepositoryInterface;
use App\Modules\Simulation\Entities\UniverseEntity;

class UniverseEloquentRepository implements UniverseRepositoryInterface
{
    public function findById(int $id): ?UniverseEntity
    {
        $model = UniverseModel::find($id);
        
        return $model ? $this->toEntity($model) : null;
    }

    public function save(UniverseEntity $entity): void
    {
        $model = UniverseModel::findOrFail($entity->id);
        
        $stateVector = $entity->stateVector;
        $stateVector['entropy'] = $entity->entropy;
        $stateVector['stability_index'] = $entity->stabilityIndex;

        $model->update([
            'current_tick' => $entity->currentTick,
            'observation_load' => $entity->observationLoad,
            'state_vector' => $stateVector,
            'kernel_genome' => $entity->kernelGenome,
            'status' => $entity->status,
            'entropy' => $entity->entropy,
            'structural_coherence' => $entity->structuralCoherence,
            'observer_bonus' => $entity->observerBonus,
            'fitness_score' => $entity->fitnessScore,
        ]);
    }

    public function findActiveByWorldId(int $worldId): array
    {
        return UniverseModel::where('world_id', $worldId)
            ->where('status', 'active')
            ->get()
            ->map(fn (UniverseModel $m) => $this->toEntity($m))
            ->all();
    }

    public function updateStatus(int $id, string $status): void
    {
        UniverseModel::where('id', $id)->update(['status' => $status]);
    }

    private function toEntity(UniverseModel $model): UniverseEntity
    {
        $sv = $model->state_vector ?? [];

        return new UniverseEntity(
            id: (int) $model->id,
            worldId: (int) $model->world_id,
            name: $model->name ?? "Universe #{$model->id}",
            currentTick: (int) ($model->current_tick ?? 0),
            entropy: (float) ($model->entropy ?? $sv['entropy'] ?? 0.0),
            stabilityIndex: (float) ($sv['stability_index'] ?? 0.0),
            observationLoad: (float) ($model->observation_load ?? 0.0),
            stateVector: $sv,
            kernelGenome: $model->kernel_genome ?? [],
            status: $model->status ?? 'active',
            structuralCoherence: (float) ($model->structural_coherence ?? 1.0),
            observerBonus: (float) ($model->observer_bonus ?? 0.0),
            fitnessScore: (float) ($model->fitness_score ?? 0.0)
        );
    }
}

