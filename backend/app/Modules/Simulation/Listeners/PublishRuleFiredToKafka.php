<?php

namespace App\Modules\Simulation\Listeners;

use App\Contracts\SimulationEventStreamProducerInterface;
use App\Modules\Simulation\Events\SimulationEventOccurred;

/**
 * Publish RuleFired (and other simulation events) to Kafka (Phase 1 event stream).
 */
class PublishRuleFiredToKafka
{
    public function __construct(
        protected SimulationEventStreamProducerInterface $producer
    ) {}

    public function handle(SimulationEventOccurred $event): void
    {
        $this->producer->publishRuleFired(
            $event->universeId,
            $event->tick,
            $event->type,
            $event->payload
        );
    }
}

