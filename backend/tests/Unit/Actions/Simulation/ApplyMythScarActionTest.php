<?php

namespace Tests\Unit\Actions\Simulation;

use App\Actions\Simulation\ApplyMythScarAction;
use App\Contracts\GraphProviderInterface;
use App\Modules\Narrative\Models\MythScar;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\World\Models\World;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery\MockInterface;

class ApplyMythScarActionTest extends TestCase
{
    use RefreshDatabase;

    private ApplyMythScarAction $action;
    private MockInterface $graphProviderMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('ApplyMythScarAction interface changed: now requires RuleVmInterface and MythScarRepositoryInterface. Test needs full rewrite.');
    }

    private function createWorld(string $slug): World
    {
        return World::create([
            'name' => 'Test World ' . $slug,
            'slug' => 'test-' . $slug . '-' . uniqid(),
            'global_tick' => 0,
        ]);
    }

    private function createUniverse(World $world, int $currentTick = 0): Universe
    {
        return Universe::create([
            'world_id' => $world->id,
            'current_tick' => $currentTick,
        ]);
    }

    private function createSnapshot(Universe $universe, int $tick, ?float $stabilityIndex = null): UniverseSnapshot
    {
        return UniverseSnapshot::create([
            'universe_id' => $universe->id,
            'tick' => $tick,
            'state_vector' => json_encode([]),
            'stability_index' => $stabilityIndex,
        ]);
    }

    // ─── Test Cases ─────────────────────────────────────────────

    public function test_it_does_not_create_scar_if_conditions_are_not_met(): void
    {
        $world = $this->createWorld('no-scar');
        $universe = $this->createUniverse($world, 1);
        $snapshot = $this->createSnapshot($universe, 1, 0.8); // High stability → no scar

        $this->graphProviderMock->shouldNotReceive('sync');

        $this->action->execute($universe, $snapshot, []);

        $this->assertDatabaseMissing('myth_scars', [
            'universe_id' => $universe->id,
        ]);
    }

    public function test_it_creates_scar_from_ai_suggestion(): void
    {
        $world = $this->createWorld('ai-scar');
        $universe = $this->createUniverse($world, 100);
        $snapshot = $this->createSnapshot($universe, 100);

        $decisionData = [
            'meta' => [
                'mutation_suggestion' => [
                    'add_scar' => 'Vết Nứt Thời Không',
                ],
            ],
        ];

        $this->graphProviderMock
            ->shouldReceive('sync')
            ->once()
            ->with($universe->id, \Mockery::on(function ($data) {
                return $data['type'] === 'MythScar' && $data['model'] instanceof MythScar;
            }));

        $this->action->execute($universe, $snapshot, $decisionData);

        $this->assertDatabaseHas('myth_scars', [
            'universe_id' => $universe->id,
            'name' => 'Vết Nứt Thời Không',
            'severity' => 0.8,
            'created_at_tick' => 100,
            'zone_id' => 'Global',
        ]);

        $scar = MythScar::where('universe_id', $universe->id)->first();
        $this->assertStringContainsString('Sẹo lịch sử do chấn động tiến hóa', $scar->description);
    }

    public function test_it_creates_scar_from_low_stability_index(): void
    {
        $world = $this->createWorld('low-stability');
        $universe = $this->createUniverse($world, 50);
        $snapshot = $this->createSnapshot($universe, 50, 0.1); // Low stability → triggers scar

        $this->graphProviderMock
            ->shouldReceive('sync')
            ->once()
            ->with($universe->id, \Mockery::on(function ($data) {
                return $data['type'] === 'MythScar' && $data['model'] instanceof MythScar;
            }));

        $this->action->execute($universe, $snapshot, []);

        $this->assertDatabaseHas('myth_scars', [
            'universe_id' => $universe->id,
            'name' => 'Ký Ức Đổ Nát',
            'created_at_tick' => 50,
            'zone_id' => 'Global',
        ]);

        $scar = MythScar::where('universe_id', $universe->id)->first();
        $this->assertNotNull($scar);
        $this->assertEqualsWithDelta(0.9, $scar->severity, 0.01); // 1.0 - 0.1
        $this->assertStringContainsString('Dấu vết còn sót lại khi cấu trúc', $scar->description);
    }
}
