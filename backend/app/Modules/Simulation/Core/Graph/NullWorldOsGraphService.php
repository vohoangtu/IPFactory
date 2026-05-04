<?php

namespace App\Modules\Simulation\Core\Graph;

use App\Modules\Simulation\Core\Contracts\WorldOsGraphServiceInterface;

/** No-op implementation when graph sync is disabled. */
final class NullWorldOsGraphService implements WorldOsGraphServiceInterface
{
    public function syncEvent(array $eventData): void
    {
    }
}
