<?php

namespace Tests\Feature;

use App\Modules\World\Models\Multiverse;
use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Narrative\Models\HistoricalFact;
use App\Simulation\Domain\EngineResult;
use App\Simulation\Domain\TickContext;
use App\Simulation\Engines\Social\TradeEngine;
use App\Simulation\Runtime\State\ReadOnlyWorldState;
use App\Simulation\Runtime\State\WorldState;
use App\Simulation\SimulationKernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PureEngineTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_enforces_read_only_state_in_engines()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Architecture Violation: Engine attempted to mutate state key 'foo' directly. Engines must return Effects.");

        $state = new WorldState(['foo' => 'bar']);
        $readOnly = new ReadOnlyWorldState($state);
        
        $readOnly->set('foo', 'baz');
    }

    /** @test */
    public function trade_engine_returns_effects_instead_of_mutating_directly()
    {
        $engine = app(TradeEngine::class);
        $state = new WorldState([
            'market_health' => 0.5,
            'zones' => [
                ['id' => 1, 'state' => [], 'fields' => ['wealth' => 0.8]],
                ['id' => 2, 'state' => [], 'fields' => ['wealth' => 0.2]],
            ]
        ]);
        $ctx = new TickContext(1, 1, 123);

        $result = $engine->handle(new ReadOnlyWorldState($state), $ctx);

        $this->assertInstanceOf(EngineResult::class, $result);
        $this->assertNotEmpty($result->stateChanges);
        
        // Ensure state was NOT mutated
        $this->assertEquals(0.5, $state->get('market_health'));
    }

    /** @test */
    public function kernel_persists_causal_links_to_historical_facts()
    {
        // Manual creation
        $multiverse = Multiverse::create(['name' => 'Test Multiverse', 'slug' => 'test-multiverse']);
        $world = World::create([
            'multiverse_id' => $multiverse->id,
            'name' => 'Test World',
            'slug' => 'test-world',
            'base_genre' => 'wuxia',
            'is_autonomic' => true,
            'global_tick' => 1
        ]);
        $universe = Universe::create([
            'world_id' => $world->id,
            'multiverse_id' => $multiverse->id,
            'name' => 'Test Universe', 
            'status' => 'active', 
            'seed' => 123,
            'current_tick' => 10
        ]);
        $snapshot = UniverseSnapshot::create([
            'universe_id' => $universe->id, 
            'tick' => 10, 
            'entropy' => 0.5, 
            'stability_index' => 0.5, 
            'metrics' => [], 
            'state_vector' => ['tick' => 10]
        ]);
        
        $parentChronicle = Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => 9,
            'to_tick' => 9,
            'type' => 'event',
            'raw_payload' => ['action' => 'test'],
        ]);

        // Mock an engine that returns an event with a causal link to the parent
        $engine = new class implements \App\Simulation\Contracts\SimulationEngine {
            public $parentId;
            public function handle(WorldState $state, TickContext $ctx): EngineResult {
                $res = EngineResult::empty();
                $res->addEvent(['type' => 'Fire', 'payload' => ['description' => 'Forest fire started']]);
                $res->linkEvent('Fire', $this->parentId);
                return $res;
            }
            public function name(): string { return 'MockEngine'; }
            public function version(): string { return '1.0.0'; }
            public function priority(): int { return 1; }
            public function phase(): string { return 'social'; }
            public function tickRate(): int { return 1; }
            public function isParallelSafe(): bool { return false; }
            public function priorityCategory(): string { return 'STOCHASTIC'; }
        };
        $engine->parentId = $parentChronicle->id;

        // Setup registry for the test
        $registry = app(\App\Simulation\EngineRegistry::class);
        $registry->register($engine);

        $kernel = app(SimulationKernel::class);
        $worldState = new WorldState(['tick' => 10], [], [], [], []);
        $ctx = new TickContext($universe->id, 10, 123);

        $kernel->runTick($worldState, $ctx);

        // Verify HistoricalFact has the parent_id
        $fact = HistoricalFact::where('universe_id', $universe->id)
            ->where('category', 'Fire')
            ->first();

        $this->assertNotNull($fact, "HistoricalFact should be created for 'Fire' event");
        $this->assertEquals($parentChronicle->id, $fact->parent_id, "HistoricalFact should have the correct parent_id from causalLinks");
    }
}
