<?php

namespace App\Modules\Intelligence\Services\Morphogenesis;

/**
 * DNA for an Evolved Archetype.
 */
class ArchetypeGenome
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        /** @var array<string, float> dot product weights against state vector */
        public readonly array $attractorVector,
        /** @var array<string, float> changes applied on winning */
        public readonly array $impactVector,
        /** @var array Meta-data about lineage */
        public readonly array $metadata = []
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'attractor_vector' => $this->attractorVector,
            'impact_vector' => $this->impactVector,
            'metadata' => $this->metadata,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? uniqid('gen_'),
            $data['name'] ?? 'Experimental X',
            $data['attractor_vector'] ?? [],
            $data['impact_vector'] ?? [],
            $data['metadata'] ?? []
        );
    }
}
