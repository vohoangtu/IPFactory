<?php

namespace App\Modules\Psychology\Dsl;

/**
 * ExpressionEngine – whitelist-based numeric expression evaluator.
 *
 * Evaluates DSL expression strings like "fear * 0.6 + stress * 0.3 - trust * 0.2"
 * using a safe subset of PHP math: no eval(), no arbitrary code.
 *
 * Allowed: numbers, +, -, *, /, (, ), and variable names from whitelist.
 * Variables are replaced with float values before evaluation.
 */
final class ExpressionEngine
{
    /**
     * Variables allowed in expressions.
     * Extends to include any keys from the context array.
     */
    private const ALLOWED_VARS = [
        'fear', 'anger', 'sadness', 'joy', 'stress', 'trust',
        'entropy', 'trauma', 'danger', 'stability',
        'openness', 'conscientiousness', 'extraversion', 'agreeableness', 'neuroticism',
    ];

    /**
     * Evaluate a DSL expression string with the given context variables.
     *
     * @param string              $expression  e.g. "fear * 0.6 + stress * 0.3"
     * @param array<string,float> $context     e.g. ['fear' => 0.7, 'stress' => 0.3, ...]
     * @return float
     */
    public function evaluate(string $expression, array $context): float
    {
        $expr = $expression;

        // Replace variable names with their float values (sorted longest-first to avoid partial replacement)
        $vars = $this->allowedVarsFromContext($context);
        uasort($vars, fn($a, $b) => strlen((string)$b) <=> strlen((string)$a));

        foreach ($vars as $varName => $value) {
            $expr = preg_replace('/\b' . preg_quote($varName, '/') . '\b/', (string)(float)$value, $expr);
        }

        // Validate: only numbers, operators, parens, spaces, dots
        if (!preg_match('/^[\d\s\.\+\-\*\/\(\)]+$/', $expr)) {
            return 0.0;
        }

        // Safe evaluation using tokenized arithmetic
        try {
            $result = $this->calculate($expr);
            return is_nan($result) || is_infinite($result) ? 0.0 : (float) $result;
        } catch (\Throwable) {
            return 0.0;
        }
    }

    /**
     * Merge allowed vars list with context keys (context wins on overlap).
     *
     * @return array<string,float>
     */
    private function allowedVarsFromContext(array $context): array
    {
        $result = [];
        foreach (self::ALLOWED_VARS as $var) {
            if (array_key_exists($var, $context)) {
                $result[$var] = (float) $context[$var];
            }
        }
        // Also allow any numeric context keys not in base whitelist
        foreach ($context as $key => $value) {
            if (is_string($key) && is_numeric($value) && preg_match('/^[a-z_]+$/', $key)) {
                $result[$key] = (float) $value;
            }
        }
        return $result;
    }

    /**
     * Simple recursive-descent calculator.
     * Handles +, -, *, / and parentheses.
     */
    private function calculate(string $expr): float
    {
        $expr = trim($expr);
        // Remove surrounding parens
        if (strlen($expr) > 1 && $expr[0] === '(' && $this->matchingParen($expr, 0) === strlen($expr) - 1) {
            $expr = trim(substr($expr, 1, -1));
        }

        // Find last + or - outside parens
        $pos = $this->findLastAddSub($expr);
        if ($pos !== -1) {
            $left  = substr($expr, 0, $pos);
            $op    = $expr[$pos];
            $right = substr($expr, $pos + 1);
            $l = $this->calculate($left);
            $r = $this->calculate($right);
            return $op === '+' ? $l + $r : $l - $r;
        }

        // Find last * or / outside parens
        $pos = $this->findLastMulDiv($expr);
        if ($pos !== -1) {
            $left  = substr($expr, 0, $pos);
            $op    = $expr[$pos];
            $right = substr($expr, $pos + 1);
            $l = $this->calculate($left);
            $r = $this->calculate($right);
            if ($op === '/' && $r == 0) {
                return 0.0;
            }
            return $op === '*' ? $l * $r : $l / $r;
        }

        return (float) trim($expr);
    }

    private function findLastAddSub(string $expr): int
    {
        $depth  = 0;
        $last   = -1;
        $len    = strlen($expr);
        for ($i = 0; $i < $len; $i++) {
            $c = $expr[$i];
            if ($c === '(') $depth++;
            elseif ($c === ')') $depth--;
            elseif ($depth === 0 && ($c === '+' || $c === '-') && $i > 0) {
                $last = $i;
            }
        }
        return $last;
    }

    private function findLastMulDiv(string $expr): int
    {
        $depth  = 0;
        $last   = -1;
        $len    = strlen($expr);
        for ($i = 0; $i < $len; $i++) {
            $c = $expr[$i];
            if ($c === '(') $depth++;
            elseif ($c === ')') $depth--;
            elseif ($depth === 0 && ($c === '*' || $c === '/') && $i > 0) {
                $last = $i;
            }
        }
        return $last;
    }

    private function matchingParen(string $expr, int $open): int
    {
        $depth = 0;
        for ($i = $open; $i < strlen($expr); $i++) {
            if ($expr[$i] === '(') $depth++;
            elseif ($expr[$i] === ')') {
                $depth--;
                if ($depth === 0) return $i;
            }
        }
        return -1;
    }
}
