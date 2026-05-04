<?php

namespace App\Modules\Simulation\Core\Events;

use App\Modules\Simulation\Core\Events\Contracts\SimulationEventInterface;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SpeciesExtinctEvent implements SimulationEventInterface
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $universeId,
        public int $tick,
        public array $payload = []
    ) {}

    public function getUniverseId(): int
    {
        return $this->universeId;
    }

    public function getType(): string
    {
        return 'species_extinct';
    }

    public function getTick(): int
    {
        return $this->tick;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
