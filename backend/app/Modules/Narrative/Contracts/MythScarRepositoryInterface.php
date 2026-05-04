<?php

namespace App\Modules\Narrative\Contracts;

use App\Modules\Narrative\Entities\MythScarEntity;

interface MythScarRepositoryInterface
{
    public function findById(int $id): ?MythScarEntity;
    public function save(MythScarEntity $entity): MythScarEntity;
    public function countUnresolved(int $universeId): int;
    /** @return MythScarEntity[] */
    public function findByUniverse(int $universeId): array;
}
