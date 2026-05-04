<?php

namespace App\Modules\Simulation\Vocation\Entities;

class ActorMasteryEntity
{
    public function __construct(
        public int $actorId,
        public string $vocationId,
        public int $level,
        public float $experience
    ) {}
}
