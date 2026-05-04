<?php

namespace App\Modules\Simulation\Entities;

/**
 * SnapshotEntity — Đại diện cho bản ghi trạng thái tại một thời điểm (tick).
 */
class SnapshotEntity
{
    public function __construct(
        public ?int $id,
        public int $universeId,
        public int $tick,
        public array $stateVector,
        public float $entropy,
        public array $metrics = [],
        public ?string $engineManifest = null,
        public ?\DateTimeInterface $createdAt = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'universe_id' => $this->universeId,
            'tick' => $this->tick,
            'state_vector' => $this->stateVector,
            'entropy' => $this->entropy,
            'metrics' => $this->metrics,
            'engine_manifest' => $this->engineManifest,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
        ];
    }
}
