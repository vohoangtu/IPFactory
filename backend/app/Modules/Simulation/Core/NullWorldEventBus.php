<?php

namespace App\Modules\Simulation\Core;

use App\Modules\Simulation\Core\Contracts\WorldEventBusInterface;
use App\Modules\Simulation\Core\Events\WorldEvent;

/** No-op event bus for tests. */
final class NullWorldEventBus implements WorldEventBusInterface
{
    public function publish(WorldEvent $event): void
    {
    }
}
