<?php

namespace App\Modules\Simulation\Vocation\DSL\Nodes;

class VariableNode extends Node
{
    public function __construct(public string $name) {}
}
