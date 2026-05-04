<?php

namespace App\Modules\Simulation\Entities;

class TrajectoryEntity
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $universeId,
        public readonly int $targetTick,
        public readonly string $phenomenonDescription,
        public readonly float $probability,
        public readonly string $convergenceType,
        public bool $isFulfilled = false
    ) {}
}
