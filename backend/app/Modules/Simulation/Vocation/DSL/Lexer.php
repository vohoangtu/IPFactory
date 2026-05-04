<?php

namespace App\Modules\Simulation\Vocation\DSL;

class Lexer
{
    /**
     * Tokenize the input string.
     * Tokens: numbers, variables, operators (+ - * /), parentheses, commas.
     */
    public function tokenize(string $input): array
    {
        preg_match_all('/\d+(\.\d+)?|[a-zA-Z_][a-zA-Z0-9_]*|[\+\-\*\/\(\),]/', $input, $matches);
        return $matches[0] ?? [];
    }
}
