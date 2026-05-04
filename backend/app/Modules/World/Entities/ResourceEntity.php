<?php

namespace App\Modules\World\Entities;

class ResourceEntity
{
    public function __construct(
        public string $id,
        public string $type,
        public float $quantity,
        public float $scarcity = 0.5,
        public ?int $zoneId = null
    ) {}

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'quantity' => $this->quantity,
            'scarcity' => $this->scarcity,
            'zone_id' => $this->zoneId,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? 'unknown',
            type: $data['type'] ?? 'unknown',
            quantity: (float)($data['quantity'] ?? 0),
            scarcity: (float)($data['scarcity'] ?? 0.5),
            zoneId: $data['zone_id'] ?? null
        );
    }
}
