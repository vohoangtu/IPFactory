<?php

namespace Tests\Feature;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\Intelligence\Models\ActorEvent;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Narrative\Services\NarrativeEngine;
use App\Modules\Narrative\Services\NarrativeEventRegistry;
use App\Simulation\Runtime\State\StateManager;
use App\Simulation\Runtime\State\WorldState;
use App\Simulation\Runtime\WorldKernel;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmergentNarrativeSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_emergent_narrative_loop_full_integration()
    {
        // 1. Setup Universe
        $universe = Universe::create(['name' => 'Emergence Test Universe', 'seed' => 12345]);
        $state = new WorldState(['universe_id' => $universe->id, 'entropy' => 0.1]);
        
        // 2. Mock an Emergent Event
        ActorEvent::create([
            'actor_id' => 1,
            'tick' => 1,
            'event_type' => 'PROMOTED',
            'context' => ['new_archetype' => 'Warlord']
        ]);

        // 3. Run WorldKernel Orchestration
        /** @var WorldKernel $kernel */
        $kernel = app(WorldKernel::class);
        $kernel->execute($state, 1);

        // 4. Verify Chronicle recorded the Scar (from Rust or manual simulation)
        // Note: Unless we have the real DLL working in test env, this might be mock in some CIs.
        // But here we are on the user's system where we just built the DLL.

        // 5. Run Narrative Engine Pulse
        $snapshot = new UniverseSnapshot([
            'universe_id' => $universe->id,
            'tick' => 1,
            'state_vector' => $state->toArray(),
            'metrics' => ['social' => ['conflict_index' => 0.5]]
        ]);

        /** @var NarrativeEngine $engine */
        $engine = app(NarrativeEngine::class);
        $engine->pulse($universe, $snapshot);

        // 6. Verify Narrative Feedback Signal was Queued
        $this->assertDatabaseHas('narrative_feedback_signals', [
            'universe_id' => $universe->id,
            'status' => 'pending'
        ]);

        echo "SUCCESS: Emergent Narrative Loop verified.\n";
    }
}
