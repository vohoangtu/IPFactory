<?php

namespace App\Modules\Intelligence\Entities;

class IdeaEntity
{
    public function __construct(
        public string $name,
        public float $appeal = 0.5,
        public float $spreadRate = 0.1,
        public string $category = 'myth',
        public array $metadata = []
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'appeal' => $this->appeal,
            'spread_rate' => $this->spreadRate,
            'category' => $this->category,
            'metadata' => $this->metadata,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? 'Untitled Idea',
            appeal: (float)($data['appeal'] ?? 0.5),
            spreadRate: (float)($data['spread_rate'] ?? 0.1),
            category: $data['category'] ?? 'myth',
            metadata: $data['metadata'] ?? []
        );
    }
}
