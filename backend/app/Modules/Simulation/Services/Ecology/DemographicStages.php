<?php

namespace App\Modules\Simulation\Services\Ecology;

/**
 * Demographic transition 4-stage model (Doc §13).
 */
final class DemographicStages
{
    public const STAGE_1_HIGH_BIRTH_HIGH_DEATH = 'stage_1';
    public const STAGE_2_HIGH_BIRTH_LOWER_DEATH = 'stage_2';
    public const STAGE_3_LOWER_BIRTH_LOW_DEATH = 'stage_3';
    public const STAGE_4_AGING_SOCIETY = 'stage_4';

    public static function stageFromRates(float $birthRate, float $deathRate, float $urbanRatio = 0.0): string
    {
        if ($birthRate > 0.03 && $deathRate > 0.02) {
            return self::STAGE_1_HIGH_BIRTH_HIGH_DEATH;
        }
        if ($birthRate > 0.025 && $deathRate < 0.02) {
            return self::STAGE_2_HIGH_BIRTH_LOWER_DEATH;
        }
        if ($birthRate < 0.02 && $deathRate < 0.015 && $urbanRatio < 0.7) {
            return self::STAGE_3_LOWER_BIRTH_LOW_DEATH;
        }
        return self::STAGE_4_AGING_SOCIETY;
    }
}
