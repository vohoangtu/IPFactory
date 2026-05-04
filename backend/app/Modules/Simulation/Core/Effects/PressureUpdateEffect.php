<?php

namespace App\Modules\Simulation\Core\Effects;

use App\Modules\Simulation\Core\Contracts\Effect;
use App\Modules\Simulation\Core\Runtime\State\WorldStateMutable;

/**
 * Updates state_vector.pressures with new values (e.g. from CosmicPressureEngine).
 *
 * @param array<string, float> $pressures keys: innovation, entropy, order, myth, conflict, ascension
 */
final class PressureUpdateEffect implements Effect
{
    public function __construct(
        private readonly array $pressures,
    ) {
    }

    public function apply(WorldStateMutable $state): void
    {
        $vec = $state->getStateVector();
        $vec['pressures'] = array_merge($vec['pressures'] ?? [], $this->pressures);
        $state->setStateVector($vec);
    }
}
