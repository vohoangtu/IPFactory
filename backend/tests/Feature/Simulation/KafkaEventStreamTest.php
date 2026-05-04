<?php

namespace Tests\Feature\Simulation;

use App\Events\Simulation\SimulationEventStreamReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class KafkaEventStreamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        config(['worldos.event_stream.kafka_enabled' => true]);
        config(['worldos.event_stream.rest_proxy_url' => 'http://redpanda:8082']);

        // Mock Multiverse & Universe to bypass Foreign Key constraint
        $multiverseId = DB::table('multiverses')->insertGetId([
            'name' => 'Test Multiverse',
            'slug' => 'test-multiverse-kafka',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $worldId = DB::table('worlds')->insertGetId([
            'multiverse_id' => $multiverseId,
            'name' => 'Kafka World',
            'slug' => 'kafka-world',
            'world_seed' => 12345,
            'global_tick' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('universes')->insert([
            'id' => 999,
            'world_id' => $worldId,
            'name' => 'Kafka Universe',
            'description' => 'Test',
            'entropy' => 0.0,
            'status' => 'active',
            'current_tick' => 45,
            'state_vector' => json_encode([]),
            'laws_of_physics' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_kafka_consumer_command_fetches_records_and_dispatches_events()
    {
        Event::fake([
            SimulationEventStreamReceived::class,
        ]);

        $mockPayload = [
            'value' => [
                'universe_id' => 999,
                'tick' => 45,
                'type' => 'simulation_advanced',
                'event_name' => 'NEW_AGE',
                'payload' => ['foo' => 'bar'],
            ]
        ];

        Http::fake([
            'http://redpanda:8082/consumers/*/instances/*/records*' => Http::sequence()
                ->push([$mockPayload], 200)
                ->push([], 200),
            'http://redpanda:8082/*' => Http::response([], 200),
        ]);

        $this->artisan('worldos:kafka-consume-events', ['--once' => true])
            ->assertExitCode(0);

        // Assert DB insertion
        $this->assertDatabaseHas('world_events', [
            'universe_id' => 999,
            'tick' => 45,
            'type' => 'NEW_AGE',
            'payload' => json_encode(['foo' => 'bar']),
        ]);

        // Assert Event dispatched
        Event::assertDispatched(SimulationEventStreamReceived::class, function ($event) {
            return $event->universeId === 999 
                && $event->tick === 45 
                && $event->type === 'NEW_AGE'
                && $event->payload === ['foo' => 'bar'];
        });
    }
}
