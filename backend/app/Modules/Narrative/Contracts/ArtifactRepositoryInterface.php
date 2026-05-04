<?php

namespace App\Modules\Narrative\Contracts;

use App\Modules\Narrative\Entities\ArtifactEntity;

interface ArtifactRepositoryInterface
{
    public function findById(int $id): ?ArtifactEntity;
    public function save(ArtifactEntity $entity): ArtifactEntity;
    public function delete(int $id): bool;
    /** @return ArtifactEntity[] */
    public function findByUniverse(int $universeId): array;
}
