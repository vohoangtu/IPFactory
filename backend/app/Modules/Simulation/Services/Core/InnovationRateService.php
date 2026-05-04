<?php

namespace App\Modules\Simulation\Services\Core;

/**
 * Innovation rate formula (Doc §9): knowledge_stock × curiosity_density × economic_surplus × institution_strength.
 */
final class InnovationRateService
{
    public function compute(
        float $knowledgeStock,
        float $curiosityDensity,
        float $economicSurplus,
        float $institutionStrength
    ): float {
        return max(0.0, min(1.0,
            $knowledgeStock * $curiosityDensity * max(0.01, $economicSurplus) * $institutionStrength
        ));
    }

    public const TECHNOLOGY_LEVEL_PRIMITIVE = 'primitive';
    public const TECHNOLOGY_LEVEL_AGRICULTURAL = 'agricultural';
    public const TECHNOLOGY_LEVEL_INDUSTRIAL = 'industrial';
    public const TECHNOLOGY_LEVEL_MODERN = 'modern';
    public const TECHNOLOGY_LEVEL_DIGITAL = 'digital';

    public static function technologyLevelFromKnowledge(float $knowledgeCore): string
    {
        if ($knowledgeCore < 0.2) {
            return self::TECHNOLOGY_LEVEL_PRIMITIVE;
        }
        if ($knowledgeCore < 0.4) {
            return self::TECHNOLOGY_LEVEL_AGRICULTURAL;
        }
        if ($knowledgeCore < 0.6) {
            return self::TECHNOLOGY_LEVEL_INDUSTRIAL;
        }
        if ($knowledgeCore < 0.85) {
            return self::TECHNOLOGY_LEVEL_MODERN;
        }
        return self::TECHNOLOGY_LEVEL_DIGITAL;
    }
}

