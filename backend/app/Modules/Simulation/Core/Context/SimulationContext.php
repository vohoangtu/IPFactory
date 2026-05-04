<?php
namespace App\Modules\Simulation\Core\Context;

class SimulationContext {
    public function __construct(public readonly int $tick = 1) {}
    public function getTick(): int { return $this->tick; }
}
