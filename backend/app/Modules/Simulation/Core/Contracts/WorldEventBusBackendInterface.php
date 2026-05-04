<?php

namespace App\Modules\Simulation\Core\Contracts;

use App\Modules\Simulation\Core\Events\WorldEvent;

/**
 * Backend for WorldEventBus: persist and/or stream events, dispatch Laravel event.
 * Implementations: Database (DB + Event::dispatch), RedisStream (XADD + optional persist + dispatch).
 */
interface WorldEventBusBackendInterface
{
    public function publish(WorldEvent $event): void;
}
