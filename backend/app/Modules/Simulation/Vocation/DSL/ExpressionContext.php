<?php

namespace App\Modules\Simulation\Vocation\DSL;

class ExpressionContext
{
    public function __construct(
        protected array $variables = []
    ) {}

    public function get(string $key): float
    {
        return (float)($this->variables[$key] ?? 0);
    }

    public function all(): array
    {
        return $this->variables;
    }

    public function toArray(): array
    {
        return $this->variables;
    }
}
