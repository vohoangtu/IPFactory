<?php

namespace App\Modules\Simulation\Core\Effects;

use App\Modules\Simulation\Core\Contracts\Effect;
use App\Modules\Simulation\Core\Runtime\State\WorldStateMutable;

final class EntropyShiftEffect implements Effect
{
    public function __construct(
        private readonly float $delta,
    ) {
    }

    public function apply(WorldStateMutable $state): void
    {
        $vec = $state->getStateVector();
        $entropy = (float) ($vec['entropy'] ?? 0);
        $vec['entropy'] = max(0, min(1.0, $entropy + $this->delta));
        $state->setStateVector($vec);
    }
}
