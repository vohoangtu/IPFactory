<?php

namespace App\Modules\Simulation\Vocation\DSL\Nodes;

class BinaryOpNode extends Node
{
    public function __construct(
        public string $op,
        public Node $left,
        public Node $right
    ) {}
}
