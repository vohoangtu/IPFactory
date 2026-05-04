<?php

namespace App\Modules\Simulation\Core\Runtime\Events;

use App\Modules\Simulation\Core\Runtime\Contracts\SimulationEvent;

class AscensionEvent implements SimulationEvent
{
    public function __construct(
        public readonly int $oldLevel,
        public readonly int $newLevel,
        public readonly int $tick
    ) {}

    public function type(): string
    {
        return 'cosmic.ascension';
    }

    public function payload(): array
    {
        return [
            'old_level' => $this->oldLevel,
            'new_level' => $this->newLevel,
            'tick' => $this->tick
        ];
    }
}
