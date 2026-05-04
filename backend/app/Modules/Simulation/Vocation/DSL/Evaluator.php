<?php

namespace App\Modules\Simulation\Vocation\DSL;

use App\Modules\Simulation\Vocation\DSL\Nodes\BinaryOpNode;
use App\Modules\Simulation\Vocation\DSL\Nodes\FunctionNode;
use App\Modules\Simulation\Vocation\DSL\Nodes\Node;
use App\Modules\Simulation\Vocation\DSL\Nodes\NumberNode;
use App\Modules\Simulation\Vocation\DSL\Nodes\VariableNode;

class Evaluator
{
    protected array $functions = [];

    public function __construct()
    {
        $this->functions = [
            'min' => fn($a, $b) => min($a, $b),
            'max' => fn($a, $b) => max($a, $b),
            'rand' => fn($a, $b) => mt_rand() / mt_getrandmax() * ($b - $a) + $a,
        ];
    }

    public function evaluate(Node $node, ExpressionContext $ctx): float
    {
        return match (true) {
            $node instanceof NumberNode => $node->value,

            $node instanceof VariableNode => $ctx->get($node->name),

            $node instanceof BinaryOpNode => $this->evalBinary($node, $ctx),

            $node instanceof FunctionNode => $this->evalFunction($node, $ctx),

            default => 0.0
        };
    }

    protected function evalBinary(BinaryOpNode $node, ExpressionContext $ctx): float
    {
        $left = $this->evaluate($node->left, $ctx);
        $right = $this->evaluate($node->right, $ctx);

        return match ($node->op) {
            '+' => $left + $right,
            '-' => $left - $right,
            '*' => $left * $right,
            '/' => $right != 0 ? $left / $right : 0.0,
            default => 0.0
        };
    }

    protected function evalFunction(FunctionNode $node, ExpressionContext $ctx): float
    {
        $args = array_map(fn($n) => $this->evaluate($n, $ctx), $node->args);
        
        if (!isset($this->functions[$node->name])) {
            return 0.0;
        }

        return ($this->functions[$node->name])(...$args);
    }
}
