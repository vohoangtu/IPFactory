<?php

declare(strict_types=1);

namespace App\Support\Broadcasting;

trait EmitsWorldEvent
{
    private ?WorldEventEnvelope $worldEventEnvelope = null;

    abstract protected function toEnvelope(): WorldEventEnvelope;

    public function envelope(): WorldEventEnvelope
    {
        return $this->worldEventEnvelope ??= $this->toEnvelope();
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return $this->envelope()->toArray();
    }
}
