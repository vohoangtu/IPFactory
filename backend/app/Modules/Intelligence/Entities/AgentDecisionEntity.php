<?php

namespace App\Modules\Intelligence\Entities;

class AgentDecisionEntity
{
    public function __construct(
        public readonly int $actorId,
        public readonly int $universeId,
        public readonly int $tick,
        public readonly string $actionType,
        public readonly ?int $targetId,
        public readonly float $utilityScore,
        public readonly array $impact = [],
        public readonly array $traitsSnapshot = [],
        public readonly array $contextSnapshot = []
    ) {}

    /**
     * Kiểm tra xem quyết định có gây ra sự thay đổi lớn tới vận mệnh không.
     */
    public function isHighImpact(): bool
    {
        return ($this->impact['entropy_delta'] ?? 0) > 0.1 
            || ($this->impact['stability_delta'] ?? 0) < -0.1;
    }
}
