<?php

declare(strict_types=1);

namespace App\Support\Broadcasting;

use Illuminate\Support\Str;

/**
 * Phong bì sự kiện thống nhất — hợp đồng broadcast duy nhất giữa backend
 * và frontend Observatory. Mọi event realtime đều bọc payload trong cấu trúc này.
 */
final class WorldEventEnvelope
{
    public readonly string $id;

    public readonly string $occurredAt;

    public function __construct(
        public readonly string $type,
        public readonly int $tick,
        public readonly int $universeId,
        public readonly ?int $worldId = null,
        public readonly string $severity = 'info',
        public readonly array $payload = [],
        ?string $id = null,
        ?string $occurredAt = null,
    ) {
        $this->id = $id ?? (string) Str::uuid();
        $this->occurredAt = $occurredAt ?? date(DATE_ATOM);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'tick' => $this->tick,
            'universe_id' => $this->universeId,
            'world_id' => $this->worldId,
            'severity' => $this->severity,
            'occurred_at' => $this->occurredAt,
            'payload' => $this->payload,
        ];
    }
}
