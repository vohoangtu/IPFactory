<?php

namespace App\Modules\Intelligence\Contracts;

use App\Modules\Intelligence\Entities\AgentDecisionEntity;

interface AgentDecisionRepositoryInterface
{
    public function save(AgentDecisionEntity $decision): void;
    
    /**
     * @return AgentDecisionEntity[]
     */
    public function findByActor(int $actorId, int $limit = 50): array;
}
