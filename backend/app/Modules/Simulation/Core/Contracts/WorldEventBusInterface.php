<?php

namespace App\Modules\Simulation\Core\Contracts;

use App\Modules\Simulation\Core\Events\WorldEvent;

interface WorldEventBusInterface
{
    public function publish(WorldEvent $event): void;
}
