<?php

declare(strict_types=1);

namespace Tests\Feature\Broadcasting;

use App\Modules\Narrative\Events\HistoricalEpochShifted;
use App\Modules\Simulation\Events\AnomalyDetected;
use App\Modules\Simulation\Events\AutopoiesisMutationApplied;
use App\Modules\Simulation\Events\EpochTransitioned;
use App\Modules\Simulation\Events\SimulationEventStreamReceived;
use App\Modules\Simulation\Events\UniversePulsed;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\SocialGraph\Events\CelebrityEmerged;
use App\Modules\World\Events\ArtifactDiscovered;
use App\Modules\World\Models\Epoch;
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

    public function test_universe_pulsed_contract(): void
    {
        $universe = Universe::factory()->create();
        $snapshot = (new UniverseSnapshot())->forceFill([
            'tick' => 8, 'entropy' => 0.42, 'stability_index' => 0.9, 'metrics' => ['population' => 10],
        ]);
        $event = new UniversePulsed($universe, $snapshot);

        $this->assertSame(["universes:{$universe->id}"], $this->channelNames($event));
        $this->assertSame('universe.pulsed', $event->broadcastAs());
        $data = $event->broadcastWith();
        $this->assertEnvelope($data, 'universe.pulsed', 8, $universe->id);
        $this->assertSame(0.42, $data['payload']['entropy']);
    }

    public function test_epoch_transitioned_contract(): void
    {
        $universe = Universe::factory()->create();
        $old = (new Epoch())->forceFill(['id' => 1, 'name' => 'Bronze']);
        $new = (new Epoch())->forceFill(['id' => 2, 'name' => 'Iron']);
        $event = new EpochTransitioned($universe, $old, $new, 100);

        $this->assertSame(["universes:{$universe->id}", 'public:universes'], $this->channelNames($event));
        $this->assertSame('epoch.transitioned', $event->broadcastAs());
        $data = $event->broadcastWith();
        $this->assertEnvelope($data, 'epoch.transitioned', 100, $universe->id);
        $this->assertSame('notable', $data['severity']);
        $this->assertSame('Iron', $data['payload']['new_epoch']['name']);
    }

    public function test_simulation_event_stream_received_contract(): void
    {
        $event = new SimulationEventStreamReceived(universeId: 5, tick: 3, type: 'engine.custom', payload: ['x' => 1], occurredAt: '2026-07-15T00:00:00+00:00');

        $this->assertSame(['universes:5'], $this->channelNames($event));
        $this->assertSame('simulation.event', $event->broadcastAs());
        $data = $event->broadcastWith();
        $this->assertEnvelope($data, 'simulation.event', 3, 5);
        $this->assertSame('engine.custom', $data['payload']['stream_type']);
    }

    public function test_envelope_survives_queue_serialization_roundtrip(): void
    {
        $event = new ArtifactDiscovered(universeId: 5, tick: 42, zoneId: 1, artifactId: 7, mass: 1.5, knowledgeEncoded: 0.8);
        $original = $event->envelope();

        $restored = unserialize(serialize($event));

        $this->assertSame($original->id, $restored->envelope()->id);
        $this->assertSame($original->occurredAt, $restored->envelope()->occurredAt);
        $this->assertSame($original->tick, $restored->envelope()->tick);
    }

    public function test_model_backed_envelope_survives_roundtrip_with_stable_tick(): void
    {
        $universe = Universe::factory()->create(['current_tick' => 7]);
        $event = new AnomalyDetected($universe, ['title' => 'Spike', 'description' => 'x', 'severity' => 'high']);
        $originalId = $event->envelope()->id;

        // Simulate tick advancing after dispatch but before the queue worker runs
        $universe->update(['current_tick' => 99]);
        $restored = unserialize(serialize($event));

        $this->assertSame($originalId, $restored->envelope()->id);
        $this->assertSame(7, $restored->envelope()->tick);
    }
}
