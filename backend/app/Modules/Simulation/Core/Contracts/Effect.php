<?php

namespace App\Modules\Simulation\Core\Contracts;

use App\Modules\Simulation\Core\Runtime\State\WorldStateMutable;

interface Effect
{
    public function apply(WorldStateMutable $state): void;
}
