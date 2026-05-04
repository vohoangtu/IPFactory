<?php

namespace App\Modules\Simulation\Core\Runtime\Causality;

/**
 * ImpactReport – The formal way for a System to report what it did during a tick.
 * 
 * Part of V81 "Reality OS" - 100% Causal Accuracy.
 */
class ImpactReport
{
    /** @var CausalLink[] */
    public array $links = [];

    public function __construct(
        public readonly string $systemName,
        public readonly string $phase,
        public readonly string $category,
        public readonly string $description = ''
    ) {}

    public function addLink(CausalLink $link): self
    {
        $this->links[] = $link;
        return $this;
    }

    public function log(string $srcType, $srcId, string $rel, string $tgtType, $tgtId, float $mag = 1.0, float $prob = 1.0, array $meta = []): self
    {
        return $this->addLink(CausalLink::create($srcType, $srcId, $rel, $tgtType, $tgtId, $mag, $prob, $meta));
    }

    public function hasImpacts(): bool
    {
        return !empty($this->links);
    }
}
