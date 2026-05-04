<?php

namespace App\Modules\Simulation\Core\Support;

/**
 * Shared rule evaluation for event triggers and attractor activation.
 * All rules must match (AND). Supports >=, <=, >, <, ==, !=.
 */
final class RuleEngine
{
    /**
     * @param array<int, array{key: string, op: string, value: mixed}> $rules
     * @param array<string, mixed> $state unused; values resolved via $getValue
     * @param callable(string): mixed $getValue returns value for key (e.g. from state_vector root/metrics/pressures)
     */
    public function evaluate(array $rules, array $state, callable $getValue): bool
    {
        if (empty($rules)) {
            return false;
        }
        foreach ($rules as $rule) {
            $key = $rule['key'] ?? null;
            $op = $rule['op'] ?? '>=';
            $value = $rule['value'] ?? null;
            if ($key === null || !array_key_exists('value', $rule)) {
                return false;
            }
            $actual = $getValue($key);
            if (!$this->evaluateRule($actual, $op, $value)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param mixed $actual
     * @param mixed $value
     */
    public function evaluateRule(mixed $actual, string $op, mixed $value): bool
    {
        $actual = is_numeric($actual) ? (float) $actual : $actual;
        $value = is_numeric($value) ? (float) $value : $value;

        return match ($op) {
            '>=' => $actual !== null && $actual >= $value,
            '<=' => $actual !== null && $actual <= $value,
            '>' => $actual !== null && $actual > $value,
            '<' => $actual !== null && $actual < $value,
            '==' => $actual == $value,
            '!=' => $actual != $value,
            default => false,
        };
    }
}
