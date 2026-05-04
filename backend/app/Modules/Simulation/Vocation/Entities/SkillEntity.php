<?php

namespace App\Modules\Simulation\Vocation\Entities;

class SkillEntity
{
    public function __construct(
        public int $id,
        public string $vocationId,
        public string $name,
        public array $element,
        public int $cost,
        public string $ruleDsl,
        public array $metadata = []
    ) {}
}
