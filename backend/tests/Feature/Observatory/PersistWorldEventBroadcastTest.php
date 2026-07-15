<?php

declare(strict_types=1);

namespace Tests\Feature\Observatory;

use App\Modules\Narrative\Events\HistoricalEpochShifted;
use App\Modules\Simulation\Events\AnomalyDetected;
use App\Modules\World\Models\Universe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PersistWorldEventBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_narrative_event_is_persisted_to_world_events(): void
    {
        // world_events.universe_id có FK -> universes.id (được enforce trên sqlite test),
        // nên cần một Universe thật thay vì universeId literal như trong brief.
        $universe = Universe::factory()->create(['current_tick' => 42]);

        event(new HistoricalEpochShifted(universeId: $universe->id, tick: 42, zoneId: 1, eventType: 'war', impactScore: 0.7, triggerData: []));

        $row = DB::table('world_events')->where('universe_id', $universe->id)->first();

        $this->assertNotNull($row);
        $this->assertSame('history.shifted', $row->type);
        $this->assertSame(42, (int) $row->tick);
        $payload = json_decode($row->payload, true);
        $this->assertSame('notable', $payload['severity']);
        $this->assertSame('war', $payload['data']['event_type']);
    }

    public function test_anomaly_event_is_persisted(): void
    {
        $universe = Universe::factory()->create(['current_tick' => 7]);

        event(new AnomalyDetected($universe, ['title' => 'Spike', 'description' => 'x', 'severity' => 'high']));

        $this->assertDatabaseHas('world_events', [
            'universe_id' => $universe->id,
            'type' => 'anomaly.detected',
            'tick' => 7,
        ]);
    }
}
