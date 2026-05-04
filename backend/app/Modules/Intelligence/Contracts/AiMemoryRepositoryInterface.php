<?php

namespace App\Modules\Intelligence\Contracts;

use App\Modules\Intelligence\Entities\AiMemoryEntity;

interface AiMemoryRepositoryInterface
{
    public function save(AiMemoryEntity $memory): void;
    
    /**
     * @return AiMemoryEntity[]
     */
    public function search(int $universeId, string $query, int $limit = 10): array;
}
