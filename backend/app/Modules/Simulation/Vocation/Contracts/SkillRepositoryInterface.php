<?php

namespace App\Modules\Simulation\Vocation\Contracts;

use App\Modules\Simulation\Vocation\Entities\SkillEntity;

interface SkillRepositoryInterface
{
    public function findById(int $id): ?SkillEntity;
    public function getByVocation(int $vocationId): array;
    public function save(SkillEntity $entity): void;
}
