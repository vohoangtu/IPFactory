<?php

namespace App\Modules\Simulation\Entities;

class RelicEntity
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $worldId,
        public readonly int $originUniverseId,
        public readonly string $name,
        public readonly string $rarity,
        public readonly string $description,
        public readonly array $powerVector,
        public readonly array $metadata = []
    ) {}

    public static function createNew(
        int $worldId,
        int $originUniverseId,
        string $name,
        string $rarity,
        string $description,
        array $powerVector,
        array $metadata = []
    ): self {
        return new self(
            id: null,
            worldId: $worldId,
            originUniverseId: $originUniverseId,
            name: $name,
            rarity: $rarity,
            description: $description,
            powerVector: $powerVector,
            metadata: $metadata
        );
    }
}
