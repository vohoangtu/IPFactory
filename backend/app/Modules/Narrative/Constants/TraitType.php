<?php

namespace App\Modules\Narrative\Constants;

/**
 * TraitType constants for agent and civilization characteristics.
 */
class TraitType
{
    public const DOMINANCE      = 1;
    public const AMBITION       = 2;
    public const COERCION       = 3;
    public const LOYALTY        = 4;
    public const EMPATHY        = 5;
    public const SOLIDARITY     = 6;
    public const CONFORMITY     = 7;
    public const PRAGMATISM     = 8;
    public const CURIOSITY      = 9;
    public const DOGMATISM      = 10;
    public const RISK_TOLERANCE = 11;
    public const FEAR           = 12;
    public const VENGEANCE      = 13;
    public const HOPE           = 14;
    public const GRIEF          = 15;
    public const PRIDE          = 16;
    public const SHAME          = 17;

    public static function get(array $traits, int $type): float
    {
        return (float) ($traits[$type] ?? 0.0);
    }
}
