<?php

namespace App\Modules\Narrative\Entities;

class ChronicleEntity
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $universeId,
        public readonly ?int $parentId,
        public readonly ?int $actorId,
        public readonly ?string $worldEventId,
        public readonly int $fromTick,
        public readonly int $toTick,
        public readonly string $type,
        public readonly ?string $content,
        public readonly float $importance,
        public readonly array $perceivedArchiveSnapshot = [],
        public readonly array $rawPayload = []
    ) {}

    public static function create(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            universeId: $data['universe_id'],
            parentId: $data['parent_id'] ?? null,
            actorId: $data['actor_id'] ?? null,
            worldEventId: $data['world_event_id'] ?? null,
            fromTick: $data['from_tick'],
            toTick: $data['to_tick'],
            type: $data['type'],
            content: $data['content'],
            importance: (float)($data['importance'] ?? 0.0),
            perceivedArchiveSnapshot: $data['perceived_archive_snapshot'] ?? [],
            rawPayload: $data['raw_payload'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'universe_id' => $this->universeId,
            'parent_id' => $this->parentId,
            'actor_id' => $this->actorId,
            'world_event_id' => $this->worldEventId,
            'from_tick' => $this->fromTick,
            'to_tick' => $this->toTick,
            'type' => $this->type,
            'content' => $this->content,
            'importance' => $this->importance,
            'perceived_archive_snapshot' => $this->perceivedArchiveSnapshot,
            'raw_payload' => $this->rawPayload,
        ];
    }
}
