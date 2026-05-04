<?php

namespace App\Modules\Institutions\Entities;

class SupremeEntity
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $universeId,
        public string $name,
        public string $entityType,
        public string $domain,
        public ?string $description = null,
        public float $powerLevel = 1.0,
        public array $alignment = [],
        public float $karma = 0.5,
        public array $karmaMetadata = [],
        public string $status = 'ASCENDED',
        public ?int $ascendedAtTick = null,
        public ?int $fallenAtTick = null,
        public readonly ?int $actorId = null
    ) {}

    /**
     * Tác động nghiệp lực (Karma integration).
     */
    public function adjustKarma(float $delta, string $reason): void
    {
        $this->karma = max(0.0, min(1.0, $this->karma + $delta));
        $this->karmaMetadata[] = [
            'delta' => $delta,
            'reason' => $reason,
            'timestamp' => now()->toDateTimeString()
        ];
    }

    public function isFallen(): bool
    {
        return $this->status === 'FALLEN' || $this->fallenAtTick !== null;
    }
}
