<?php

namespace App\Modules\Simulation\Vocation\DSL\Nodes;

class FunctionNode extends Node
{
    public function __construct(
        public string $name,
        public array $args
    ) {}
}
