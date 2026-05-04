<?php

namespace App\Contracts\Repositories;

interface BranchEventRepositoryInterface
{
    /**
     * Whether a fork event already exists for this universe at this tick (idempotent replay).
     */
    public function existsFork(int $universeId, int $fromTick): bool;

    /**
     * Whether this universe has already been the parent of at least one fork (one fork per universe).
     */
    public function hasForkAsParent(int $universeId): bool;
}
