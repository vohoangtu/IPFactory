<?php

namespace App\Contracts;

/**
 * Publish simulation events to Kafka (Phase 1 event stream).
 * See backend/docs/EVENT_STREAM_SCHEMA.md for topic and message format.
 */
interface SimulationEventStreamProducerInterface
{
    /**
     * Publish SimulationAdvanced: sau mỗi advance (snapshot đã lưu).
     */
    public function publishSimulationAdvanced(int $universeId, int $tick, array $payload = []): void;

    /**
     * Publish RuleFired (hoặc event khác từ rule VM / engine): event_name, payload.
     */
    public function publishRuleFired(int $universeId, int $tick, string $eventName, array $payload = []): void;
}
