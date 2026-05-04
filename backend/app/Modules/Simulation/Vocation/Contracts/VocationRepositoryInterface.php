<?php

namespace App\Modules\Simulation\Vocation\Contracts;

use App\Modules\Simulation\Vocation\Entities\VocationEntity;

interface VocationRepositoryInterface
{
    public function findById(string $id): ?VocationEntity;
    public function getAll(): array;
    public function save(VocationEntity $entity): void;
}
