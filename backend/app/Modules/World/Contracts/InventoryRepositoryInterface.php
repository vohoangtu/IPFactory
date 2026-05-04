<?php

namespace App\Modules\World\Contracts;

use App\Modules\World\Entities\Inventory;

interface InventoryRepositoryInterface
{
    public function findByActorId(string $actorId): ?Inventory;
    public function save(Inventory $inventory): void;
    public function delete(string $actorId): void;
}
