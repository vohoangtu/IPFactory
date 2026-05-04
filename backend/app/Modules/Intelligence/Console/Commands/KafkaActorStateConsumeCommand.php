<?php

namespace App\Modules\Intelligence\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Modules\Simulation\Core\Runtime\State\StateManager;
use App\Modules\Intelligence\Entities\ActorState;

class KafkaActorStateConsumeCommand extends Command
{
    protected $signature = 'worldos:kafka-consume-actor-states
                            {--once : Chỉ poll một lần rồi thoát}
                            {--timeout=10 : Giây chờ khi poll}';

    protected $description = 'Consume actor state updates from Kafka (Redpanda) and update database/state manager.';

    public function __construct(
        private StateManager $stateManager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $kafkaEnabled = config('worldos.event_stream.kafka_enabled', false);
        $baseUrl = rtrim(config('worldos.event_stream.rest_proxy_url', ''), '/');
        
        if (!$kafkaEnabled || empty($baseUrl)) {
            $this->warn('Kafka is not enabled or REST proxy URL is missing.');
            return self::FAILURE;
        }

        $topic = 'actor-state-updates';
        $group = 'worldos-actor-state-consumer';
        $instanceId = 'backend-' . gethostname() . '-' . uniqid();

        if (!$this->createConsumer($baseUrl, $group, $instanceId)) {
            return self::FAILURE;
        }

        $this->info("Consumer created: {$instanceId} (Group: {$group})");

        try {
            if (!$this->subscribe($baseUrl, $group, $instanceId, [$topic])) {
                return self::FAILURE;
            }

            $once = $this->option('once');
            $timeout = (int) $this->option('timeout');

            $this->info("Subscribed to topic: {$topic}. Waiting for messages...");

            do {
                $records = $this->poll($baseUrl, $group, $instanceId, $timeout);
                
                if (!empty($records)) {
                    $processed = $this->processRecords($records);
                    if ($processed > 0) {
                        $this->info("Processed {$processed} actor state updates.");
                    }
                }

                if ($once) break;

                if (empty($records)) {
                    usleep(100_000); // 0.1s
                }
            } while (true);

        } finally {
            $this->deleteConsumer($baseUrl, $group, $instanceId);
        }

        return self::SUCCESS;
    }

    private function createConsumer(string $baseUrl, string $group, string $instanceId): bool
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(10)
                ->accept('application/vnd.kafka.v2+json')
                ->post("{$baseUrl}/consumers/{$group}", [
                    'name' => $instanceId,
                    'format' => 'json',
                    'auto.offset.reset' => 'latest',
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            $this->error("Failed to create consumer: " . $e->getMessage());
            return false;
        }
    }

    private function subscribe(string $baseUrl, string $group, string $instanceId, array $topics): bool
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(10)
                ->accept('application/vnd.kafka.v2+json')
                ->post("{$baseUrl}/consumers/{$group}/instances/{$instanceId}/subscription", [
                    'topics' => $topics
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            $this->error("Failed to subscribe: " . $e->getMessage());
            return false;
        }
    }

    private function poll(string $baseUrl, string $group, string $instanceId, int $timeout): array
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout($timeout + 2)
                ->accept('application/vnd.kafka.json.v2+json')
                ->get("{$baseUrl}/consumers/{$group}/instances/{$instanceId}/records?timeout=" . ($timeout * 1000));

            if (!$response->successful()) {
                return [];
            }

            return $response->json() ?? [];
        } catch (\Exception $e) {
            $this->warn("Poll failed: " . $e->getMessage());
            return [];
        }
    }

    private function processRecords(array $records): int
    {
        $count = 0;
        foreach ($records as $record) {
            $data = $record['value'] ?? null;
            if (!$data || !isset($data['outputs'])) continue;

            // Dữ liệu từ Rust engine (ProcessActorsSoaResponse)
            $outputs = $data['outputs'];
            $tick = (int) ($record['key'] ?? 0);

            DB::transaction(function () use ($outputs, $tick) {
                foreach ($outputs as $output) {
                    $actorId = $output['actor_id'];
                    
                    // Cập nhật Database
                    DB::table('actors')->where('id', $actorId)->update([
                        'hunger' => $output['new_hunger'],
                        'energy' => $output['new_energy'],
                        'trauma' => $output['new_trauma'],
                        'updated_at' => now(),
                    ]);

                    // Cập nhật Faction Loyalty nếu có
                    if (!empty($output['new_faction_ids'])) {
                        foreach ($output['new_faction_ids'] as $idx => $fId) {
                            $loyalty = $output['new_faction_loyalty'][$idx] ?? 1.0;
                            DB::table('actor_faction')->updateOrInsert(
                                ['actor_id' => $actorId, 'faction_id' => $fId],
                                ['loyalty' => $loyalty, 'updated_at' => now()]
                            );
                        }
                    }

                    // Tải lại vào StateManager nếu Actor đang active trong bộ nhớ
                    // (Đây là bước quan trọng cho tính nhất quán)
                    $this->stateManager->forgetActor($actorId);
                }
            });

            $count += count($outputs);
        }

        return $count;
    }

    private function deleteConsumer(string $baseUrl, string $group, string $instanceId): void
    {
        Http::timeout(5)->delete("{$baseUrl}/consumers/{$group}/instances/{$instanceId}");
    }
}

