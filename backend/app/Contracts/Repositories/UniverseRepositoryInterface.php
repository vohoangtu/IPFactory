<?php

namespace App\Contracts\Repositories;

use App\Modules\World\Models\Universe;

interface UniverseRepositoryInterface
{
    /**
     * Get a universe by ID.
     */
    public function find(int $id): ?Universe;

    /**
     * Create a new universe.
     */
    public function create(array $data): Universe;

    /**
     * Update an existing universe.
     */
    public function update(int $id, array $data): bool;

    /**
     * Fork a universe (duplicate with new params).
     */
    public function fork(Universe $parent, int $tick, array $newParams): Universe;
}

