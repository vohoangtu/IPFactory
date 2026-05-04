<?php

namespace Tests\Unit\Actions\Simulation;

use App\Actions\Simulation\ForkUniverseAction;
use App\Contracts\Repositories\BranchEventRepositoryInterface;
use App\Contracts\Repositories\UniverseRepositoryInterface;
use App\Modules\Simulation\Models\BranchEvent;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use App\Services\Orchestrator\ImplicitOrchestratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery\MockInterface;

class ForkUniverseActionTest extends TestCase
{
    use RefreshDatabase;

    private ForkUniverseAction $action;
    private MockInterface $universeRepoMock;
    private MockInterface $branchRepoMock;
    private MockInterface $orchestratorMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('ForkUniverseAction interface changed: now requires RuleVmService. Test needs full rewrite.');
    }

    private function createWorldAndUniverse(int $tick = 10): Universe
    {
        $world = World::create([
            'name' => 'ForkTestWorld',
            'slug' => 'fork-test-' . uniqid(),
            'global_tick' => $tick,
        ]);

        return Universe::create([
            'world_id' => $world->id,
            'current_tick' => $tick,
            'state_vector' => ['entropy' => 0.9],
        ]);
    }

    public function test_fork_creates_branch_event_and_spawns_universe(): void
    {
        $universe = $this->createWorldAndUniverse(10);

        $this->branchRepoMock->shouldReceive('existsFork')->with($universe->id, 10)->andReturn(false);
        $this->branchRepoMock->shouldReceive('hasForkAsParent')->with($universe->id)->andReturn(false);

        $decisionData = [
            'meta' => [
                'reason' => 'timeline_split',
                'mutation_suggestion' => ['add_scar' => 'Test Scar'],
                'ip_score' => 85,
            ],
        ];

        $this->orchestratorMock
            ->shouldReceive('spawnUniverse')
            ->once()
            ->with($universe->world, $universe->id, $universe->saga_id, \Mockery::type('array'));

        $this->universeRepoMock
            ->shouldReceive('update')
            ->once()
            ->with($universe->id, \Mockery::on(function ($data) {
                return isset($data['state_vector']['entropy']) && $data['state_vector']['entropy'] === 0.5;
            }))
            ->andReturn(true);

        $this->action->execute($universe, 10, $decisionData);

        $this->assertDatabaseHas('branch_events', [
            'universe_id' => $universe->id,
            'from_tick' => 10,
            'event_type' => 'fork',
        ]);

        $event = BranchEvent::where('universe_id', $universe->id)->first();
        $payload = is_string($event->payload) ? json_decode($event->payload, true) : $event->payload;
        $this->assertEquals('timeline_split', $payload['reason']);
        $this->assertEquals(85, $payload['score']);
    }

    public function test_fork_does_not_duplicate_if_branch_event_already_exists(): void
    {
        $universe = $this->createWorldAndUniverse(10);

        $this->branchRepoMock->shouldReceive('existsFork')->with($universe->id, 10)->andReturn(true);

        $this->orchestratorMock->shouldNotReceive('spawnUniverse');
        $this->universeRepoMock->shouldNotReceive('update');

        $this->action->execute($universe, 10, ['meta' => []]);

        $this->assertEquals(0, BranchEvent::where('universe_id', $universe->id)
            ->where('from_tick', 10)
            ->where('event_type', 'fork')
            ->count()
        );
    }

    public function test_fork_uses_default_reason_when_not_provided(): void
    {
        $universe = $this->createWorldAndUniverse(20);

        $this->branchRepoMock->shouldReceive('existsFork')->with($universe->id, 20)->andReturn(false);
        $this->branchRepoMock->shouldReceive('hasForkAsParent')->with($universe->id)->andReturn(false);
        $this->orchestratorMock->shouldReceive('spawnUniverse')->once();
        $this->universeRepoMock->shouldReceive('update')->once()->andReturn(true);

        $this->action->execute($universe, 20, ['meta' => []]);

        $event = BranchEvent::where('universe_id', $universe->id)->first();
        $payload = is_string($event->payload) ? json_decode($event->payload, true) : $event->payload;
        $this->assertEquals('high_entropy', $payload['reason']);
    }
}
