<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChronicleAnimationTest extends TestCase
{
    use RefreshDatabase;

    private function createTestUniverse(): int
    {
        $multiverseId = DB::table('multiverses')->insertGetId([
            'name' => 'Test Multiverse',
            'slug' => 'test-multi-' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $worldId = DB::table('worlds')->insertGetId([
            'name' => 'Test World',
            'slug' => 'test-world-' . uniqid(),
            'world_seed' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('universes')->insertGetId([
            'multiverse_id' => $multiverseId,
            'world_id' => $worldId,
            'name' => 'Test Universe',
            'status' => 'active',
            'current_tick' => 1,
            'entropy' => 0.5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createChronicle(int $universeId, ?array $animationScript = null): int
    {
        return DB::table('chronicles')->insertGetId([
            'universe_id' => $universeId,
            'from_tick' => 1,
            'to_tick' => 10,
            'type' => 'world',
            'content' => 'Test chronicle content',
            'importance' => 0.8,
            'animation_script' => $animationScript ? json_encode($animationScript) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_chronicle_list_includes_has_animation_flag(): void
    {
        $universeId = $this->createTestUniverse();
        $this->createChronicle($universeId, null);
        $this->createChronicle($universeId, ['version' => '1.0', 'scenes' => []]);

        $response = $this->getJson("/api/worldos/universes/{$universeId}/chronicles");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(2, $data);

        $withoutAnimation = collect($data)->firstWhere('animation_script', null);
        $withAnimation = collect($data)->first(fn ($c) => ! empty($c['has_animation']));

        $this->assertNotNull($withoutAnimation);
        $this->assertFalse($withoutAnimation['has_animation'] ?? false);
    }

    public function test_chronicle_show_returns_animation_script(): void
    {
        $universeId = $this->createTestUniverse();
        $script = [
            'version' => '1.0',
            'total_duration_ms' => 10000,
            'scenes' => [
                ['id' => 'scene_1', 'type' => 'establishing', 'duration_ms' => 5000],
            ],
        ];
        $chronicleId = $this->createChronicle($universeId, $script);

        $response = $this->getJson("/api/worldos/chronicles/{$chronicleId}");

        $response->assertOk();
        $response->assertJsonPath('data.has_animation', true);
        $response->assertJsonPath('data.animation_script.version', '1.0');
        $response->assertJsonPath('data.animation_script.total_duration_ms', 10000);
    }

    public function test_chronicle_show_without_animation_returns_null_script(): void
    {
        $universeId = $this->createTestUniverse();
        $chronicleId = $this->createChronicle($universeId, null);

        $response = $this->getJson("/api/worldos/chronicles/{$chronicleId}");

        $response->assertOk();
        $response->assertJsonPath('data.has_animation', false);
    }
}
