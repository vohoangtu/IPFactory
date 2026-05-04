<?php

namespace App\Modules\Simulation\Entities;

class BranchEventEntity
{
    public function __construct(
        public int $universe_id,
        public int $from_tick,
        public string $event_type, // 'fork', 'merge', etc.
        public ?int $target_universe_id = null,
        public array $metadata = [],
        public readonly ?int $id = null
    ) {}

    public static function create(array $data): self
    {
        return new self(
            universe_id: (int) $data['universe_id'],
            from_tick: (int) $data['from_tick'],
            event_type: $data['event_type'],
            target_universe_id: isset($data['target_universe_id']) ? (int) $data['target_universe_id'] : null,
            metadata: $data['metadata'] ?? [],
            id: $data['id'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'universe_id' => $this->universe_id,
            'from_tick' => $this->from_tick,
            'event_type' => $this->event_type,
            'target_universe_id' => $this->target_universe_id,
            'metadata' => $this->metadata,
        ];
    }
}
