<?php

declare(strict_types=1);

namespace Tests\Unit\Broadcasting;

use App\Support\Broadcasting\WorldEventEnvelope;
use PHPUnit\Framework\TestCase;

class WorldEventEnvelopeTest extends TestCase
{
    public function test_to_array_has_all_envelope_keys(): void
    {
        $envelope = new WorldEventEnvelope(
            type: 'epoch.transitioned',
            tick: 120,
            universeId: 5,
            worldId: 3,
            severity: 'notable',
            payload: ['old' => 'Bronze', 'new' => 'Iron'],
        );

        $data = $envelope->toArray();

        $this->assertSame(
            ['id', 'type', 'tick', 'universe_id', 'world_id', 'severity', 'occurred_at', 'payload'],
            array_keys($data)
        );
        $this->assertSame('epoch.transitioned', $data['type']);
        $this->assertSame(120, $data['tick']);
        $this->assertSame(5, $data['universe_id']);
        $this->assertSame(3, $data['world_id']);
        $this->assertSame('notable', $data['severity']);
        $this->assertSame(['old' => 'Bronze', 'new' => 'Iron'], $data['payload']);
        $this->assertNotEmpty($data['id']);
        $this->assertNotEmpty($data['occurred_at']);
    }

    public function test_id_is_stable_across_calls_and_defaults_apply(): void
    {
        $envelope = new WorldEventEnvelope(type: 'anomaly.detected', tick: 1, universeId: 9);

        $this->assertSame($envelope->toArray()['id'], $envelope->toArray()['id']);
        $this->assertSame('info', $envelope->toArray()['severity']);
        $this->assertNull($envelope->toArray()['world_id']);
        $this->assertSame([], $envelope->toArray()['payload']);
    }
}
