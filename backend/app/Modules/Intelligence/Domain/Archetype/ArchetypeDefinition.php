<?php

namespace App\Modules\Intelligence\Domain\Archetype;

use App\Modules\Intelligence\Entities\ActorState;
use App\Modules\Intelligence\Domain\BehaviorStats;

final class ArchetypeDefinition
{
    /** @var callable */
    public $scoreFunction;

    /** @var callable|null */
    public $condition;

    public function __construct(
        public readonly string $name,
        public readonly string $namePrefix,
        callable $scoreFunction,
        ?callable $condition = null,
        public readonly array $motivationVector = [],
        public readonly float $distributionTarget = 0.05
    ) {
        $this->scoreFunction = $scoreFunction;
        $this->condition = $condition;
    }

    /**
     * Tỉ lệ score dựa trên state và behavior hiện tại của Actor.
     */
    public function score(ActorState $state, BehaviorStats $stats, float $entropy = 0.5): float
    {
        return ($this->scoreFunction)($state, $stats, $entropy);
    }

    /**
     * Kiểm tra xem Archetype này có khả thi trong world này không.
     */
    public function isEligible(array $worldAxiom): bool
    {
        if ($this->condition === null) {
            return true;
        }

        return ($this->condition)($worldAxiom);
    }
}
