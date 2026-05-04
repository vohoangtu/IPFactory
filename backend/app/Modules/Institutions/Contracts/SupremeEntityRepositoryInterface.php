<?php

namespace App\Modules\Institutions\Contracts;

use App\Modules\Institutions\Entities\SupremeEntity;

interface SupremeEntityRepositoryInterface
{
    public function findById(int $id): ?SupremeEntity;
    
    /**
     * @return SupremeEntity[]
     */
    public function findByUniverse(int $universeId): array;
    
    /**
     * Save entity and return its id (created or updated).
     */
    public function save(SupremeEntity $entity): int;
}
