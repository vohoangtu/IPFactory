<?php

declare(strict_types=1);

namespace Tests\Feature\Observatory;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LoomWebhookBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_pipeline_done_broadcasts_chronicle_generated_envelope_on_narrative_channel(): void
    {
        config([
            'centrifugo.url' => 'http://centrifugo:8000',
            'centrifugo.api_key' => 'test-key',
            'services.loom.shared_secret' => 'test-secret',
        ]);
        Http::fake(['http://centrifugo:8000/api' => Http::response(['result' => []], 200)]);

        $world = World::factory()->create();
        $universe = Universe::factory()->create(['world_id' => $world->id]);

        $response = $this->postJson('/api/worldos/narrative-loom/webhook', [
            'type' => 'pipeline_done',
            'task_id' => 'task-1',
            'world_id' => $world->id,
            'final_prose' => 'Sử thi...',
            'news_headline' => 'Đế chế sụp đổ',
            'tick_start' => 10,
            'tick_end' => 20,
        ], ['X-Loom-Secret' => 'test-secret']);

        $response->assertOk();
        $this->assertDatabaseHas('chronicles', ['universe_id' => $universe->id, 'from_tick' => 10, 'to_tick' => 20]);

        Http::assertSent(function ($request) use ($universe) {
            $body = (string) $request->body();

            return str_contains($body, "universes:{$universe->id}:narrative")
                && str_contains($body, '"chronicle.generated"');
        });
    }
}
