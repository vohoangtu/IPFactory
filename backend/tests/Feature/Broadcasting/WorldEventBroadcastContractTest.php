<?php

declare(strict_types=1);

namespace Tests\Feature\Broadcasting;

use App\Modules\Narrative\Events\HistoricalEpochShifted;
use App\Modules\Simulation\Events\AnomalyDetected;
use App\Modules\Simulation\Events\AutopoiesisMutationApplied;
use App\Modules\SocialGraph\Events\CelebrityEmerged;
use App\Modules\World\Events\ArtifactDiscovered;
use App\Modules\World\Models\Universe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hợp đồng kênh + envelope cho mọi broadcast event.
 * Chặn tái diễn lệch quy ước dấu chấm / hai chấm.
 */
class WorldEventBroadcastContractTest extends TestCase
{
    use RefreshDatabase;

    /** @return string[] */
    private function channelNames(object $event): array
    {
        $channels = $event->broadcastOn();

        return array_map(fn ($c) => (string) $c, is_array($channels) ? $channels : [$channels]);
    }

    private function assertEnvelope(array $data, string $type, int $tick, int $universeId): void
    {
        $this->assertSame(
            ['id', 'type', 'tick', 'universe_id', 'world_id', 'severity', 'occurred_at', 'payload'],
            array_keys($data)
        );
        $this->assertSame($type, $data['type']);
        $this->assertSame($tick, $data['tick']);
        $this->assertSame($universeId, $data['universe_id']);
    }

    public function test_artifact_discovered_contract(): void
    {
        $event = new ArtifactDiscovered(universeId: 5, tick: 42, zoneId: 1, artifactId: 7, mass: 1.5, knowledgeEncoded: 0.8);

        $this->assertSame(['universes:5:narrative'], $this->channelNames($event));
        $this->assertSame('artifact.discovered', $event->broadcastAs());
        $this->assertEnvelope($event->broadcastWith(), 'artifact.discovered', 42, 5);
        $this->assertSame(7, $event->broadcastWith()['payload']['artifact_id']);
    }

    public function test_celebrity_emerged_contract(): void
    {
        $event = new CelebrityEmerged(universeId: 5, tick: 42, zoneId: 1, agentId: 9, fame: 0.9, vocation: 'bard');

        $this->assertSame(['universes:5:narrative'], $this->channelNames($event));
        $this->assertSame('celebrity.emerged', $event->broadcastAs());
        $this->assertEnvelope($event->broadcastWith(), 'celebrity.emerged', 42, 5);
        $this->assertSame('bard', $event->broadcastWith()['payload']['vocation']);
    }

    public function test_historical_epoch_shifted_contract(): void
    {
        $event = new HistoricalEpochShifted(universeId: 5, tick: 42, zoneId: 1, eventType: 'war', impactScore: 0.7, triggerData: ['a' => 1]);

        $this->assertSame(['universes:5:narrative'], $this->channelNames($event));
        $this->assertSame('history.shifted', $event->broadcastAs());
        $this->assertEnvelope($event->broadcastWith(), 'history.shifted', 42, 5);
        $this->assertSame('notable', $event->broadcastWith()['severity']);
    }

    public function test_anomaly_detected_contract(): void
    {
        $universe = Universe::factory()->create(['current_tick' => 77]);
        $event = new AnomalyDetected($universe, [
            'title' => 'Entropy spike',
            'description' => 'Entropy vượt ngưỡng',
            'severity' => 'medium',
        ]);

        $this->assertSame(["universes:{$universe->id}:anomaly"], $this->channelNames($event));
        $this->assertSame('anomaly.detected', $event->broadcastAs());
        $data = $event->broadcastWith();
        $this->assertEnvelope($data, 'anomaly.detected', 77, $universe->id);
        $this->assertSame('notable', $data['severity']); // medium → notable
        $this->assertSame('Entropy spike', $data['payload']['title']);

        // Test uppercase WARN severity mapping to notable
        $warnEvent = new AnomalyDetected($universe, [
            'title' => 'Drift',
            'description' => 'x',
            'severity' => 'WARN',
        ]);
        $this->assertSame('notable', $warnEvent->broadcastWith()['severity']);
    }

    public function test_autopoiesis_mutation_contract(): void
    {
        $event = new AutopoiesisMutationApplied(universeId: 5, payload: ['tick' => 12, 'rule' => 'gravity_v2']);

        $this->assertSame(['universes:5:autopoiesis'], $this->channelNames($event));
        $this->assertSame('autopoiesis.mutation', $event->broadcastAs());
        $data = $event->broadcastWith();
        $this->assertEnvelope($data, 'autopoiesis.mutation', 12, 5);
        $this->assertSame('gravity_v2', $data['payload']['rule']);
    }
}
