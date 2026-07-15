<?php

declare(strict_types=1);

namespace Tests\Feature\Observatory;

use App\Modules\Narrative\Models\Chronicle;
use App\Modules\World\Models\Universe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ObservatoryFeedTest extends TestCase
{
    use RefreshDatabase;

    private function seedEvent(int $universeId, int $tick, string $type): void
    {
        DB::table('world_events')->insert([
            'id' => (string) Str::uuid(),
            'universe_id' => $universeId,
            'tick' => $tick,
            'type' => $type,
            'payload' => json_encode(['severity' => 'notable', 'world_id' => null, 'occurred_at' => '2026-07-15T00:00:00+00:00', 'data' => ['k' => 'v']]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_feed_merges_events_and_chronicles_ordered_by_tick_desc(): void
    {
        $universe = Universe::factory()->create();
        $this->seedEvent($universe->id, 5, 'anomaly.detected');
        $this->seedEvent($universe->id, 20, 'epoch.transitioned');
        Chronicle::create(['universe_id' => $universe->id, 'from_tick' => 8, 'to_tick' => 10, 'type' => 'narrative_loom', 'importance' => 0.8, 'content' => 'Sử thi']);

        $response = $this->getJson("/api/worldos/observatory/universes/{$universe->id}/feed");

        $response->assertOk();
        $ticks = collect($response->json('data'))->pluck('tick')->all();
        $this->assertSame([20, 10, 5], $ticks);
        $kinds = collect($response->json('data'))->pluck('kind')->all();
        $this->assertSame(['event', 'chronicle', 'event'], $kinds);
        $this->assertSame(5, $response->json('meta.next_before_tick'));
    }

    public function test_feed_filters_by_types_and_tick_window(): void
    {
        $universe = Universe::factory()->create();
        $this->seedEvent($universe->id, 5, 'anomaly.detected');
        $this->seedEvent($universe->id, 20, 'epoch.transitioned');
        Chronicle::create(['universe_id' => $universe->id, 'from_tick' => 8, 'to_tick' => 10, 'type' => 'narrative_loom', 'importance' => 0.8, 'content' => 'Sử thi']);

        $onlyEpoch = $this->getJson("/api/worldos/observatory/universes/{$universe->id}/feed?types=epoch.transitioned");
        $this->assertSame(['epoch.transitioned'], collect($onlyEpoch->json('data'))->pluck('type')->all());

        $window = $this->getJson("/api/worldos/observatory/universes/{$universe->id}/feed?after_tick=5&before_tick=20");
        $this->assertSame([10], collect($window->json('data'))->pluck('tick')->all());
    }

    public function test_feed_respects_limit_and_isolates_universes(): void
    {
        $universe = Universe::factory()->create();
        $other = Universe::factory()->create();
        foreach ([1, 2, 3] as $tick) {
            $this->seedEvent($universe->id, $tick, 'anomaly.detected');
        }
        $this->seedEvent($other->id, 99, 'anomaly.detected');

        $response = $this->getJson("/api/worldos/observatory/universes/{$universe->id}/feed?limit=2");

        $this->assertCount(2, $response->json('data'));
        $this->assertSame([3, 2], collect($response->json('data'))->pluck('tick')->all());
    }

    public function test_feed_empty_universe_returns_ok_with_empty_data(): void
    {
        $universe = Universe::factory()->create();

        $response = $this->getJson("/api/worldos/observatory/universes/{$universe->id}/feed");

        $response->assertOk();
        $this->assertSame([], $response->json('data'));
        $this->assertNull($response->json('meta.next_before_tick'));
    }
}
