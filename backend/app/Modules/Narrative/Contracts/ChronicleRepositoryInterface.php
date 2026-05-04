<?php

namespace App\Modules\Narrative\Contracts;

use App\Modules\Narrative\Entities\ChronicleEntity;

interface ChronicleRepositoryInterface
{
    public function findById(int $id): ?ChronicleEntity;
    public function save(ChronicleEntity $entity): ChronicleEntity;
    /** @return ChronicleEntity[] */
    public function findByUniverse(int $universeId, int $limit = 10): array;

    /** @return ChronicleEntity[] */
    public function findUnprocessedForTicks(int $universeId, int $fromTick, int $toTick, int $limit = 100): array;
}
