<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Enums;

/**
 * SimulationPhase — The 5 canonical phases of reality in WorldOS simulation.
 *
 * Execution order is strictly enforced: Environment → Life → Mind → Social → Meta.
 * Integer backing values encode this order (1–5).
 */
enum SimulationPhase: int
{
    case Environment = 1;
    case Life        = 2;
    case Mind        = 3;
    case Social      = 4;
    case Meta        = 5;

    /**
     * Return the legacy string key used by WorldKernel orchestrationMap.
     */
    public function key(): string
    {
        return match ($this) {
            self::Environment => 'environment',
            self::Life        => 'life',
            self::Mind        => 'mind',
            self::Social      => 'social',
            self::Meta        => 'meta',
        };
    }

    /**
     * Resolve from a legacy string phase key.
     */
    public static function fromKey(string $key): self
    {
        return match ($key) {
            'environment' => self::Environment,
            'life'        => self::Life,
            'mind'        => self::Mind,
            'social'      => self::Social,
            'meta'        => self::Meta,
            default       => throw new \ValueError("Unknown simulation phase key: {$key}"),
        };
    }

    /**
     * Return all phases in execution order.
     *
     * @return self[]
     */
    public static function inOrder(): array
    {
        return [
            self::Environment,
            self::Life,
            self::Mind,
            self::Social,
            self::Meta,
        ];
    }
}
