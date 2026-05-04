<?php

namespace App\Modules\Narrative\Contracts;

use App\Modules\Narrative\Entities\DemiurgeEntity;

interface DemiurgeRepositoryInterface
{
    public function findById(int $id): ?DemiurgeEntity;
    /** @return DemiurgeEntity[] */
    public function all(): array;
    public function save(DemiurgeEntity $entity): DemiurgeEntity;
    public function decrementEssence(int $id, float $amount): void;
}
