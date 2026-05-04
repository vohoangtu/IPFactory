<?php

namespace App\Modules\World\Repositories;

use App\Modules\World\Contracts\InventoryRepositoryInterface;
use App\Modules\World\Entities\Inventory;
use Illuminate\Support\Facades\Cache;

class CacheInventoryRepository implements InventoryRepositoryInterface
{
    private const KEY_PREFIX = 'world_inventory:';

    public function findByActorId(string $actorId): ?Inventory
    {
        return Cache::get(self::KEY_PREFIX . $actorId);
    }

    public function save(Inventory $inventory): void
    {
        Cache::put(self::KEY_PREFIX . $inventory->actorId, $inventory, now()->addHours(24));
    }

    public function delete(string $actorId): void
    {
        Cache::forget(self::KEY_PREFIX . $actorId);
    }
}
