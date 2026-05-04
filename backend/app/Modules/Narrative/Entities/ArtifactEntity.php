<?php

namespace App\Modules\Narrative\Entities;

class ArtifactEntity
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $universeId,
        public readonly int $creatorActorId,
        public readonly ?int $institutionId,
        public readonly string $artifactType,
        public readonly string $title,
        public readonly string $theme,
        public readonly string $culture,
        public readonly int $tickCreated,
        public readonly float $impactScore,
        public readonly array $metadata = []
    ) {}

    public static function create(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            universeId: $data['universe_id'],
            creatorActorId: $data['creator_actor_id'],
            institutionId: $data['institution_id'] ?? null,
            artifactType: $data['artifact_type'],
            title: $data['title'],
            theme: $data['theme'],
            culture: $data['culture'],
            tickCreated: $data['tick_created'],
            impactScore: (float)($data['impact_score'] ?? 0.0),
            metadata: $data['metadata'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'universe_id' => $this->universeId,
            'creator_actor_id' => $this->creatorActorId,
            'institution_id' => $this->institutionId,
            'artifact_type' => $this->artifactType,
            'title' => $this->title,
            'theme' => $this->theme,
            'culture' => $this->culture,
            'tick_created' => $this->tickCreated,
            'impact_score' => $this->impactScore,
            'metadata' => $this->metadata,
        ];
    }
}
