<?php

namespace App\Modules\Simulation\Vocation\Engine;

use App\Modules\Simulation\Vocation\Entities\SkillEntity;

class ExecutionContext
{
    public function __construct(
        public object $actor,        // Using object for compatibility with Eloquent or pure Actor Entity
        public ?object $target = null,
        public ?SkillEntity $skill = null,
        public array $worldState = [],
        public array $position = [], // [x, y]
    ) {}

    /**
     * Runtime mutable fields
     */
    public array $modifiers = [];         
    public array $elementField = [];      
    public float $energyCostMultiplier = 1.0;
    public float $damageMultiplier = 1.0;

    /**
     * Future hooks
     */
    public array $tags = [];              
    public array $meta = [];              

    public function addModifier(string $key, float $value): void
    {
        $this->modifiers[$key] = ($this->modifiers[$key] ?? 1.0) * $value;
    }

    public function getModifier(string $key): float
    {
        return $this->modifiers[$key] ?? 1.0;
    }
}
