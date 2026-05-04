<?php

namespace App\Modules\Narrative\Entities;

class MythScarEntity
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $universeId,
        public readonly string $zoneId,
        public readonly string $name,
        public readonly string $description,
        public readonly float $severity,
        public readonly float $decayRate,
        public readonly int $createdAtTick,
        public readonly ?int $resolvedAtTick = null
    ) {}

    public static function create(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            universeId: $data['universe_id'],
            zoneId: $data['zone_id'] ?? 'Global',
            name: $data['name'],
            description: $data['description'],
            severity: (float)($data['severity'] ?? 0.5),
            decayRate: (float)($data['decay_rate'] ?? 0.005),
            createdAtTick: $data['created_at_tick'],
            resolvedAtTick: $data['resolved_at_tick'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'universe_id' => $this->universeId,
            'zone_id' => $this->zoneId,
            'name' => $this->name,
            'description' => $this->description,
            'severity' => $this->severity,
            'decay_rate' => $this->decayRate,
            'created_at_tick' => $this->createdAtTick,
            'resolved_at_tick' => $this->resolvedAtTick,
        ];
    }
}
