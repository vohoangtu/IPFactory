<?php

namespace App\Modules\Intelligence\Contracts;

use App\Modules\Intelligence\Entities\ActorEntity;

interface ActorRepositoryInterface
{
    public function findById(int $id): ?ActorEntity;
    
    /**
     * @return ActorEntity[]
     */
    public function findByUniverse(int $universeId): array;

    /**
     * @return ActorEntity[]
     */
    public function findActiveByUniverse(int $universeId): array;
    
    public function save(ActorEntity $actor): void;

    /**
     * @param ActorEntity[] $actors
     */
    public function saveBatch(array $actors): void;
    
    public function delete(int $id): void;

    /**
     * Batch-delete actors by their IDs in a single query.
     *
     * @param int[] $ids
     */
    public function deleteBatch(array $ids): void;

    public function getActiveCount(int $universeId): int;
}
