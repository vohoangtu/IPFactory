<?php

namespace App\Modules\Simulation\Vocation\DSL;

use App\Modules\Simulation\Vocation\DSL\Nodes\BinaryOpNode;
use App\Modules\Simulation\Vocation\DSL\Nodes\FunctionNode;
use App\Modules\Simulation\Vocation\DSL\Nodes\Node;
use App\Modules\Simulation\Vocation\DSL\Nodes\NumberNode;
use App\Modules\Simulation\Vocation\DSL\Nodes\VariableNode;

class Parser
{
    protected array $precedence = [
        '+' => 1,
        '-' => 1,
        '*' => 2,
        '/' => 2,
    ];

    /**
     * Parse tokens into an AST using basic Shunting-yard or similar logic.
     * Note: This is an improved version of the Shunting-yard to produce AST nodes.
     */
    public function parse(array $tokens): Node
    {
        $output = [];
        $ops = [];

        foreach ($tokens as $token) {
            if (is_numeric($token)) {
                $output[] = new NumberNode((float)$token);
            } elseif (preg_match('/^[a-zA-Z_]/', $token)) {
                // Check if it's a function (next token is '(') - Simplified for V1
                // For now, treat all names as variables unless we add function support in parser
                $output[] = new VariableNode($token);
            } elseif ($token === '(') {
                $ops[] = $token;
            } elseif ($token === ')') {
                while (!empty($ops) && end($ops) !== '(') {
                    $this->popOperator($output, $ops);
                }
                array_pop($ops); // Remove '('
            } elseif (isset($this->precedence[$token])) {
                while (!empty($ops) && end($ops) !== '(' &&
                    $this->precedence[end($ops)] >= $this->precedence[$token]) {
                    $this->popOperator($output, $ops);
                }
                $ops[] = $token;
            }
        }

        while (!empty($ops)) {
            $this->popOperator($output, $ops);
        }

        return array_pop($output) ?? new NumberNode(0);
    }

    protected function popOperator(array &$output, array &$ops): void
    {
        $op = array_pop($ops);
        $right = array_pop($output);
        $left = array_pop($output);
        $output[] = new BinaryOpNode($op, $left, $right);
    }
}
