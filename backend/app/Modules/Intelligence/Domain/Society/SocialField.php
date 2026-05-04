<?php

namespace App\Modules\Intelligence\Domain\Society;

/**
 * Value Object representing the aggregate social field of a universe.
 */
final class SocialField
{
    public function __construct(
        public readonly float $survivalField = 0.5,
        public readonly float $reproductionField = 0.5,
        public readonly float $wealthField = 0.5,
        public readonly float $powerField = 0.5,
        public readonly float $knowledgeField = 0.5,
        public readonly float $meaningField = 0.5,
        public readonly float $statusField = 0.5,
        public readonly float $belongingField = 0.5
    ) {}
}
