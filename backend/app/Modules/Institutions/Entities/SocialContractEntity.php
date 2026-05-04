<?php

namespace App\Modules\Institutions\Entities;

class SocialContractEntity
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $universeId,
        public string $type,
        public array $participants = [],
        public float $strictness = 0.5,
        public int $duration = 100,
        public int $createdAtTick = 0,
        public ?int $expiresAtTick = null,
        public ?int $institutionalEntityId = null
    ) {}

    public function isExpired(int $currentTick): bool
    {
        return $this->expiresAtTick !== null && $currentTick >= $this->expiresAtTick;
    }
}
