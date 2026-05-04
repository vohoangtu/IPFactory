<?php

namespace App\Modules\World\Contracts;

use App\Modules\World\Entities\NaturalResource;

interface ResourceRepositoryInterface
{
    public function findByLocation(int $x, int $y): array;
    public function save(NaturalResource $resource): void;
    public function findById(string $id): ?NaturalResource;
}
