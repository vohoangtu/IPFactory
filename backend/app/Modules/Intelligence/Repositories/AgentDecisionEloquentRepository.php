<?php

namespace App\Modules\Intelligence\Repositories;

use App\Modules\Intelligence\Models\AgentDecision as AgentDecisionModel;
use App\Modules\Intelligence\Contracts\AgentDecisionRepositoryInterface;
use App\Modules\Intelligence\Entities\AgentDecisionEntity;

class AgentDecisionEloquentRepository implements AgentDecisionRepositoryInterface
{
    public function save(AgentDecisionEntity $entity): void
    {
        AgentDecisionModel::create([
            'actor_id' => $entity->actorId,
            'universe_id' => $entity->universeId,
            'tick' => $entity->tick,
            'action_type' => $entity->actionType,
            'target_id' => $entity->targetId,
            'utility_score' => $entity->utilityScore,
            'impact' => $entity->impact,
            'traits_snapshot' => $entity->traitsSnapshot,
            'context_snapshot' => $entity->contextSnapshot,
        ]);
    }

    public function findByActor(int $actorId, int $limit = 50): array
    {
        return AgentDecisionModel::where('actor_id', $actorId)
            ->orderByDesc('tick')
            ->limit($limit)
            ->get()
            ->map(fn($model) => new AgentDecisionEntity(
                actorId: $model->actor_id,
                universeId: $model->universe_id,
                tick: $model->tick,
                actionType: $model->action_type,
                targetId: $model->target_id,
                utilityScore: (float) $model->utility_score,
                impact: $model->impact ?? [],
                traitsSnapshot: $model->traits_snapshot ?? [],
                contextSnapshot: $model->context_snapshot ?? []
            ))
            ->toArray();
    }
}

