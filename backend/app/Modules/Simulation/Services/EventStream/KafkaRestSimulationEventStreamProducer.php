<?php

namespace App\Modules\Simulation\Services\EventStream;

use App\Contracts\SimulationEventStreamProducerInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Publish simulation events to Kafka via REST Proxy (Confluent / Redpanda).
 * Message format: see backend/docs/EVENT_STREAM_SCHEMA.md.
 */
final class KafkaRestSimulationEventStreamProducer implements SimulationEventStreamProducerInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $topicAdvanced,
        private readonly string $topicEvents,
    ) {}

    public function publishSimulationAdvanced(int $universeId, int $tick, array $payload = []): void
    {
        $message = [
            'universe_id' => $universeId,
            'tick' => $tick,
            'type' => 'simulation_advanced',
            'event_name' => null,
            'payload' => $payload,
            'occurred_at' => now()->utc()->toIso8601String(),
        ];
        $this->send($this->topicAdvanced, $message);
    }

    public function publishRuleFired(int $universeId, int $tick, string $eventName, array $payload = []): void
    {
        $message = [
            'universe_id' => $universeId,
            'tick' => $tick,
            'type' => 'rule_fired',
            'event_name' => $eventName,
            'payload' => $payload,
            'occurred_at' => now()->utc()->toIso8601String(),
        ];
        $this->send($this->topicEvents, $message);
    }

    private function send(string $topic, array $value): void
    {
        $url = $this->baseUrl . '/topics/' . $topic;
        $body = [
            'records' => [
                ['value' => $value],
            ],
        ];

        try {
            $response = Http::timeout(5)
                ->accept('application/vnd.kafka.v2+json')
                ->withBody(json_encode($body), 'application/vnd.kafka.json.v2+json')
                ->post($url);

            if (! $response->successful()) {
                Log::warning('Event stream Kafka REST publish failed', [
                    'topic' => $topic,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Event stream Kafka REST publish error: ' . $e->getMessage(), [
                'topic' => $topic,
            ]);
        }
    }
}

