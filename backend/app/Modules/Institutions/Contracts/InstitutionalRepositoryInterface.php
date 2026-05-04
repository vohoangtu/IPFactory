<?php

namespace App\Modules\Institutions\Contracts;

use App\Modules\Institutions\Entities\InstitutionalEntity;

interface InstitutionalRepositoryInterface
{
    public function findById(int $id): ?InstitutionalEntity;
    
    /**
     * @return InstitutionalEntity[]
     */
    public function findActiveByUniverse(int $universeId): array;
    
    public function save(InstitutionalEntity $entity): void;
    
    public function delete(int $id): void;
}
