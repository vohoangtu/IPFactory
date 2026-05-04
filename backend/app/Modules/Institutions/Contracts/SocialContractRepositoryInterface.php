<?php

namespace App\Modules\Institutions\Contracts;

use App\Modules\Institutions\Entities\SocialContractEntity;

interface SocialContractRepositoryInterface
{
    public function findByUniverse(int $universeId): array;
    
    public function save(SocialContractEntity $entity): void;
    
    public function delete(int $id): void;
}
