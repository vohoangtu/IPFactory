<?php

namespace Tests\Feature\Integration;

use App\Actions\Simulation\AdvanceSimulationAction;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use App\Modules\Simulation\Jobs\AdvanceUniverseJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Mockery\MockInterface;

class AdvanceUniverseJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_dispatches_to_queue(): void
    {
        Queue::fake();

        AdvanceUniverseJob::dispatch(42, 5);

        Queue::assertPushed(AdvanceUniverseJob::class, function ($job) {
            return $job->universeId === 42 && $job->ticks === 5;
        });
    }

    public function test_job_default_ticks_is_one(): void
    {
        Queue::fake();

        AdvanceUniverseJob::dispatch(99);

        Queue::assertPushed(AdvanceUniverseJob::class, function ($job) {
            return $job->universeId === 99 && $job->ticks === 1;
        });
    }

    public function test_job_calls_advance_simulation_action(): void
    {
        $world = World::create([
            'name' => 'JobTestWorld',
            'slug' => 'job-test-' . uniqid(),
            'global_tick' => 0,
        ]);

        $universe = Universe::create([
            'world_id' => $world->id,
            'current_tick' => 0,
            'state_vector' => ['zones' => [['id' => 0, 'state' => ['base_mass' => 100], 'neighbors' => []]]],
        ]);

        $mockAction = $this->mock(AdvanceSimulationAction::class, function (MockInterface $mock) use ($universe) {
            $mock->shouldReceive('execute')
                ->once()
                ->with($universe->id, 3)
                ->andReturn(['ok' => true, 'snapshot' => []]);
        });

        $job = new AdvanceUniverseJob($universe->id, 3);
        $job->handle($mockAction);
    }

    public function test_multiple_jobs_dispatched_for_different_universes(): void
    {
        Queue::fake();

        AdvanceUniverseJob::dispatch(1, 2);
        AdvanceUniverseJob::dispatch(2, 4);
        AdvanceUniverseJob::dispatch(3, 6);

        Queue::assertPushed(AdvanceUniverseJob::class, 3);

        Queue::assertPushed(AdvanceUniverseJob::class, fn($j) => $j->universeId === 1 && $j->ticks === 2);
        Queue::assertPushed(AdvanceUniverseJob::class, fn($j) => $j->universeId === 2 && $j->ticks === 4);
        Queue::assertPushed(AdvanceUniverseJob::class, fn($j) => $j->universeId === 3 && $j->ticks === 6);
    }
}
