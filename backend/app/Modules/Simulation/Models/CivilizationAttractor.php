<?php

namespace App\Modules\Simulation\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represent a meta-attractor rule identified by the Self-Reflection system.
 */
class CivilizationAttractor extends Model
{
    protected $fillable = [
        'name',
        'description',
        'activation_rules',
        'force_map',
        'decay_rate',
    ];

    protected $casts = [
        'activation_rules' => 'array',
        'force_map' => 'array',
        'decay_rate' => 'float',
    ];

    /**
     * Check if the attractor should activate for a given state.
     */
    public function shouldActivate(array $state): bool
    {
        $rules = $this->activation_rules ?? [];
        if (empty($rules)) return false;

        foreach ($rules as $rule) {
            $key = $rule['key'] ?? '';
            $op = $rule['op'] ?? '=';
            $val = $rule['value'] ?? 0;
            $current = $state[$key] ?? 0;

            if (!$this->evalRule($current, $op, $val)) {
                return false;
            }
        }

        return true;
    }

    private function evalRule($current, $op, $expected): bool
    {
        return match ($op) {
            '>' => $current > $expected,
            '<' => $current < $expected,
            '>=' => $current >= $expected,
            '<=' => $current <= $expected,
            '=' => $current == $expected,
            default => false,
        };
    }
}
