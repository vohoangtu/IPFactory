<?php

namespace App\Modules\World\Repositories;

use App\Modules\World\Contracts\ResourceRepositoryInterface;
use App\Modules\World\Entities\NaturalResource;
use Illuminate\Support\Facades\DB;

class EloquentResourceRepository implements ResourceRepositoryInterface
{
    public function findByLocation(int $x, int $y): array
    {
        // Mocking for now, will connect to natural_resources table
        return [];
    }

    public function findById(string $id): ?NaturalResource
    {
        return null; // Mock
    }

    public function save(NaturalResource $resource): void
    {
        // Save logic to DB
    }
}
