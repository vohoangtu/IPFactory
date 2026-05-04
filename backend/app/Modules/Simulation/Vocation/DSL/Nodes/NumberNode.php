<?php

namespace App\Modules\Simulation\Vocation\DSL\Nodes;

class NumberNode extends Node
{
    public function __construct(public float $value) {}
}
