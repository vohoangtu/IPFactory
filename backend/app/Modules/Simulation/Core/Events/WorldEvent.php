<?php

namespace App\Modules\Simulation\Core\Events;

/**
 * DTO for a world event (doc §16). Immutable.
 */
final class WorldEvent
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly int $universeId,
        public readonly int $tick,
        public readonly ?string $location = null,
        public readonly array $actors = [],
        public readonly float $impactScore = 0.0,
        public readonly array $causes = [],
        public readonly array $payload = [],
        public readonly ?int $parentId = null,
    ) {
    }

    public static function create(
        string $type,
        int $universeId,
        int $tick,
        ?string $location = null,
        array $actors = [],
        float $impactScore = 0.0,
        array $causes = [],
        array $payload = [],
        ?int $parentId = null,
    ): self {
        return new self(
            id: \Illuminate\Support\Str::uuid()->toString(),
            type: $type,
            universeId: $universeId,
            tick: $tick,
            location: $location,
            actors: $actors,
            impactScore: $impactScore,
            causes: $causes,
            payload: $payload,
            parentId: $parentId,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'universe_id' => $this->universeId,
            'tick' => $this->tick,
            'location' => $this->location,
            'actors' => $this->actors,
            'impact_score' => $this->impactScore,
            'causes' => $this->causes,
            'payload' => $this->payload,
            'parent_id' => $this->parentId,
        ];
    }
}
