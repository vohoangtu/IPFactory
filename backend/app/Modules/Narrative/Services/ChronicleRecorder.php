<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Narrative\Contracts\ChronicleRepositoryInterface;
use App\Modules\Narrative\Entities\ChronicleEntity;
use App\Modules\World\Models\Universe;

class ChronicleRecorder
{
    public function __construct(
        private ChronicleRepositoryInterface $chronicleRepository
    ) {}

    public function record(
        Universe $universe,
        string $type,
        string $content,
        float $importance = 0.5,
        ?array $rawPayload = null,
        ?int $fromTick = null,
        ?int $toTick = null
    ): ChronicleEntity {
        $entity = ChronicleEntity::create([
            'universe_id' => $universe->id,
            'from_tick' => $fromTick ?? $universe->current_tick,
            'to_tick' => $toTick ?? $universe->current_tick,
            'type' => $type,
            'content' => $content,
            'importance' => $importance,
            'raw_payload' => $rawPayload ?? [
                'action' => 'legacy_event',
                'description' => $content,
            ],
        ]);

        $this->chronicleRepository->save($entity);

        return $entity;
    }
}
