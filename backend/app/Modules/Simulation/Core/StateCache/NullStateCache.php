<?php

namespace App\Modules\Simulation\Core\StateCache;

use App\Modules\Simulation\Core\Contracts\StateCacheInterface;

final class NullStateCache implements StateCacheInterface
{
    public function get(int $universeId): ?array
    {
        return null;
    }

    public function set(int $universeId, array $stateVector, int $tick): void
    {
        // no-op
    }
}
