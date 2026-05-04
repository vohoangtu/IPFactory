<?php

namespace App\Modules\Simulation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Consumer mẫu (Phase 1): đọc topic worldos.simulation.events từ Kafka REST Proxy,
 * ghi vào bảng world_events. Chạy một lần (--once) hoặc lặp (mặc định).
 */
class KafkaEventStreamConsumeCommand extends Command
{
    protected $signature = 'worldos:kafka-consume-events
                            {--once : Chỉ poll một lần rồi thoát}
                            {--timeout=5 : Giây chờ khi poll}';

    protected $description = 'Consume simulation events from Kafka (REST Proxy) and write to world_events table.';

    public function handle(): int
    {
        if (! config('worldos.event_stream.kafka_enabled', false)) {
            $this->warn('Event stream Kafka is disabled (worldos.event_stream.kafka_enabled). Enable and set rest_proxy_url to run consumer.');

            return self::FAILURE;
        }

        $baseUrl = rtrim(config('worldos.event_stream.rest_proxy_url', ''), '/');
        $topic = config('worldos.event_stream.topic_events', 'worldos.simulation.events');
        $group = 'worldos-event-stream-consumer';
        $instanceId = 'worldos-' . uniqid('', true);

        $created = $this->createConsumer($baseUrl, $group, $instanceId);
        if (! $created) {
            return self::FAILURE;
        }

        try {
            if (! $this->subscribe($baseUrl, $group, $instanceId, [$topic])) {
                return self::FAILURE;
            }

            $timeout = (int) $this->option('timeout');
            $once = $this->option('once');

            do {
                $records = $this->poll($baseUrl, $group, $instanceId, $timeout);
                $written = $this->writeRecordsToWorldEvents($records);
                if ($written > 0) {
                    $this->info("Written {$written} event(s) to world_events.");
                }
                if ($once) {
                    break;
                }
                if (empty($records)) {
                    usleep(500_000); // 0.5s before next poll
                }
            } while (true);
        } finally {
            $this->deleteConsumer($baseUrl, $group, $instanceId);
        }

        return self::SUCCESS;
    }

    private function createConsumer(string $baseUrl, string $group, string $instanceId): bool
    {
        $url = "{$baseUrl}/consumers/{$group}";
        $body = [
            'name' => $instanceId,
            'format' => 'json',
            'auto.offset.reset' => 'latest',
        ];

        $response = Http::timeout(10)
            ->accept('application/vnd.kafka.v2+json')
            ->post($url, $body);

        if (! $response->successful()) {
            $this->error('Failed to create Kafka consumer: ' . $response->body());

            return false;
        }

        return true;
    }

    private function subscribe(string $baseUrl, string $group, string $instanceId, array $topics): bool
    {
        $url = "{$baseUrl}/consumers/{$group}/instances/{$instanceId}/subscription";
        $response = Http::timeout(10)
            ->accept('application/vnd.kafka.v2+json')
            ->post($url, ['topics' => $topics]);

        if (! $response->successful()) {
            $this->error('Failed to subscribe: ' . $response->body());

            return false;
        }

        return true;
    }

    /**
     * @return array<int, array{value?: array}>
     */
    private function poll(string $baseUrl, string $group, string $instanceId, int $timeoutSec): array
    {
        $url = "{$baseUrl}/consumers/{$group}/instances/{$instanceId}/records?timeout=" . $timeoutSec * 1000;
        $response = Http::timeout($timeoutSec + 2)
            ->accept('application/vnd.kafka.json.v2+json')
            ->get($url);

        if (! $response->successful()) {
            return [];
        }

        $data = $response->json();
        if (! is_array($data)) {
            return [];
        }

        return $data;
    }

    /**
     * @param  array<int, array{value?: array}>  $records
     */
    private function writeRecordsToWorldEvents(array $records): int
    {
        $written = 0;
        foreach ($records as $record) {
            $value = $record['value'] ?? null;
            if (! is_array($value)) {
                continue;
            }
            $universeId = (int) ($value['universe_id'] ?? 0);
            $tick = (int) ($value['tick'] ?? 0);
            $msgType = $value['type'] ?? '';
            $eventName = $value['event_name'] ?? $msgType;
            $payload = $value['payload'] ?? [];
            if ($universeId <= 0) {
                continue;
            }
            $type = $eventName ?: $msgType ?: 'unknown';
            $id = (string) Str::uuid();
            try {
                DB::table('world_events')->insert([
                    'id' => $id,
                    'universe_id' => $universeId,
                    'tick' => $tick,
                    'type' => strlen($type) > 64 ? substr($type, 0, 64) : $type,
                    'payload' => json_encode($payload),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $written++;
                
                // Dispatch event broadcast realtime qua Websocket
                event(new \App\Modules\Simulation\Events\SimulationEventStreamReceived(
                    $universeId,
                    $tick,
                    $type,
                    $payload,
                    now()->utc()->toIso8601String()
                ));
            } catch (\Throwable $e) {
                $this->warn("Insert failed for record: " . $e->getMessage());
            }
        }

        return $written;
    }

    private function deleteConsumer(string $baseUrl, string $group, string $instanceId): void
    {
        $url = "{$baseUrl}/consumers/{$group}/instances/{$instanceId}";
        Http::timeout(5)->delete($url);
    }
}

