<?php

namespace App\Modules\Simulation\Core\Runtime\Causality;

/**
 * CausalLink – Represents a specific link between an action and its effect (§Level-10 Semantic History).
 */
class CausalLink
{
    public function __construct(
        public readonly string $sourceType,
        public readonly string|int $sourceId,
        public readonly string $relation,
        public readonly string $targetType,
        public readonly string|int $targetId,
        public readonly float $magnitude = 1.0,
        public readonly float $probability = 1.0,
        public readonly array $metadata = []
    ) {}

    public static function create(string $srcType, $srcId, string $rel, string $tgtType, $tgtId, float $mag = 1.0, float $prob = 1.0, array $meta = []): self
    {
        return new self($srcType, $srcId, $rel, $tgtType, $tgtId, $mag, $prob, $meta);
    }
}
