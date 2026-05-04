<?php

namespace App\Modules\Simulation\Services\EventStream;

use App\Contracts\SimulationEventStreamProducerInterface;

/**
 * No-op producer when Kafka event stream is disabled.
 */
final class NullSimulationEventStreamProducer implements SimulationEventStreamProducerInterface
{
    public function publishSimulationAdvanced(int $universeId, int $tick, array $payload = []): void
    {
        // no-op
    }

    public function publishRuleFired(int $universeId, int $tick, string $eventName, array $payload = []): void
    {
        // no-op
    }
}

