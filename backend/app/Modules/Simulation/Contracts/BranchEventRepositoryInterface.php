<?php

namespace App\Modules\Simulation\Contracts;

use App\Contracts\Repositories\BranchEventRepositoryInterface as BaseBranchEventRepositoryInterface;
use App\Modules\Simulation\Entities\BranchEventEntity;

interface BranchEventRepositoryInterface extends BaseBranchEventRepositoryInterface
{
    public function findById(int $id): ?BranchEventEntity;
    public function save(BranchEventEntity $entity): BranchEventEntity;
}
