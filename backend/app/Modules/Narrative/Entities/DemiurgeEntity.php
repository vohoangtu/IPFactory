<?php

namespace App\Modules\Narrative\Entities;

class DemiurgeEntity
{
    public function __construct(
        public readonly ?int $id = null,
        public string $name,
        public float $essence_pool = 0,
        public float $will_power = 100,
        public array $config = [],
        public ?string $status = 'active'
    ) {}

    public static function create(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'],
            essence_pool: (float)($data['essence_pool'] ?? 0),
            will_power: (float)($data['will_power'] ?? 100),
            config: $data['config'] ?? [],
            status: $data['status'] ?? 'active'
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'essence_pool' => $this->essence_pool,
            'will_power' => $this->will_power,
            'config' => $this->config,
            'status' => $this->status,
        ];
    }
}
