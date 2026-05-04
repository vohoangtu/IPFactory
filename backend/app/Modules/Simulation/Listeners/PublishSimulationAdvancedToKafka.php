<?php

namespace App\Modules\Simulation\Listeners;

use App\Contracts\SimulationEventStreamProducerInterface;
use App\Modules\Simulation\Events\UniverseSimulationPulsed;

/**
 * Publish SimulationAdvanced to Kafka (Phase 1 event stream) when advance completes.
 */
class PublishSimulationAdvancedToKafka
{
    public function __construct(
        protected SimulationEventStreamProducerInterface $producer
    ) {}

    public function handle(UniverseSimulationPulsed $event): void
    {
        $universe = $event->universe;
        $snapshot = $event->snapshot;
        $tick = (int) $snapshot->tick;

        $payload = [
            'snapshot_tick' => $tick,
            'entropy' => $snapshot->entropy ?? $universe->entropy ?? null,
            'stability_index' => $snapshot->stability_index ?? null,
        ];
        $engineResponse = $event->engineResponse ?? [];
        if (isset($engineResponse['snapshot']['sci'])) {
            $payload['sci'] = $engineResponse['snapshot']['sci'];
        }
        if (isset($engineResponse['snapshot']['instability_gradient'])) {
            $payload['instability_gradient'] = $engineResponse['snapshot']['instability_gradient'];
        }

        // Phase 100: Include transition metadata and reality strain
        $payload['transition'] = $universe->state_vector['transition'] ?? null;
        $payload['reality_strain'] = $universe->state_vector['reality_strain'] ?? 0.0;
        $payload['anomaly_probability'] = $universe->state_vector['anomaly_probability'] ?? 0.0;
        $payload['power_system'] = $universe->world->power_system_type ?? 'traditional';
        $payload['civilization_era'] = $universe->world->civilization_era ?? 'genesis';

        $this->producer->publishSimulationAdvanced((int) $universe->id, $tick, $payload);
    }
}

