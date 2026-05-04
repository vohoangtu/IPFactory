<?php

namespace App\Modules\Simulation\Listeners;

use App\Modules\Simulation\Events\PowerSystemTransitionTriggered;
use App\Modules\Simulation\Services\Transition\TransitionProcessor;
use App\Modules\Simulation\Core\Runtime\State\StateManager;
use App\Modules\World\Models\Universe;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HandlePowerSystemTransition implements ShouldQueue
{
    public function __construct(
        private readonly TransitionProcessor $processor,
        private readonly StateManager $stateManager
    ) {}

    public function handle(PowerSystemTransitionTriggered $event): void
    {
        $world = $event->world;
        $targetPowerSystem = $event->targetPowerSystem;

        Log::info("Handling Power System Transition for World {$world->id} to {$targetPowerSystem}");

        // 1. Get the primary universe for this world
        $universe = Universe::where('world_id', $world->id)->first();
        if (!$universe) {
            Log::error("No universe found for world {$world->id} during transition.");
            return;
        }

        // 2. Load the current state
        $state = $this->stateManager->load($universe);

        // 3. Process the transition
        $newState = $this->processor->process($state, $targetPowerSystem);

        // 4. Update the world model
        // transition.phase: 0 = Shock, 1 = Adaptation
        // We set it to 0 initially.
        $newState->set('transition.phase', 0);
        $newState->set('transition.target', $targetPowerSystem);
        $newState->set('transition.start_tick', $newState->getTick());

        $world->power_system_type = $targetPowerSystem;
        $world->power_system_bootstrap_energy = (float)$newState->get('power_system_bootstrap_energy', 0.0);
        $world->version += 1;
        $world->save();

        // 5. Save the state back to the universe
        $this->stateManager->save($universe);

        Log::info("Power System Transition completed for World {$world->id}. New Version: {$world->version}");
    }
}
