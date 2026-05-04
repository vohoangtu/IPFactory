<?php

namespace App\Modules\Simulation\Data\Traits;

/**
 * TraitIndex defines the 17D Trait Vector indices as per WORLDOS_V6 §3.1.
 */
final class TraitIndex
{
    // Power (0-2)
    public const DOMINANCE = 0;
    public const AMBITION = 1;
    public const COERCION = 2;

    // Social (3-6)
    public const LOYALTY = 3;
    public const EMPATHY = 4;
    public const SOLIDARITY = 5;
    public const CONFORMITY = 6;

    // Cognitive (7-10)
    public const PRAGMATISM = 7;
    public const CURIOSITY = 8;
    public const DOGMATISM = 9;
    public const RISK_TOLERANCE = 10;

    // Emotional (11-16)
    public const FEAR = 11;
    public const VENGEANCE = 12;
    public const HOPE = 13;
    public const GRIEF = 14;
    public const PRIDE = 15;
    public const SHAME = 16;
}
