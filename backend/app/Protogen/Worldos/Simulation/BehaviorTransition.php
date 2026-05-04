<?php

namespace Worldos\Simulation;

class BehaviorTransition
{
    private int $fromNodeId = 0;
    private int $toNodeId = 0;
    private string $condition = '';
    private float $weight = 1.0;

    public function setFromNodeId(int $id): static { $this->fromNodeId = $id; return $this; }
    public function getFromNodeId(): int { return $this->fromNodeId; }

    public function setToNodeId(int $id): static { $this->toNodeId = $id; return $this; }
    public function getToNodeId(): int { return $this->toNodeId; }

    public function setCondition(string $cond): static { $this->condition = $cond; return $this; }
    public function getCondition(): string { return $this->condition; }

    public function setWeight(float $w): static { $this->weight = $w; return $this; }
    public function getWeight(): float { return $this->weight; }
}
