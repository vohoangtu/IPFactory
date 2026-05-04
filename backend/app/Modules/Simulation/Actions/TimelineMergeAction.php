<?php

namespace App\Modules\Simulation\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Models\UniverseInteraction;
use App\Modules\Simulation\Models\BranchEvent;
use App\Contracts\SimulationEngineClientInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TimelineMergeAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected SimulationEngineClientInterface $simulationClient
    ) {}
    /**
     * Hợp nhất hai vũ trụ có cộng hưởng cao thành một "Prime Timeline" (§5.3).
     */
    public function execute(int $universeAId, int $universeBId): Universe
    {
        return DB::transaction(function () use ($universeAId, $universeBId) {
            $uA = Universe::findOrFail($universeAId);
            $uB = Universe::findOrFail($universeBId);

            // 1. Create New Prime Universe
            $prime = $uA->replicate();
            $prime->name = "Prime Synthesis: " . $uA->name . " & " . $uB->name;
            $prime->parent_universe_id = $uA->id; 
            $prime->status = 'active';
            $prime->save();

            // 2. Synthesize State Vector via Simulation Engine (§52)
            $snapA = $uA->latestSnapshot;
            $snapB = $uB->latestSnapshot;
            
            $result = $this->simulationClient->merge(
                json_encode($snapA->state_vector),
                json_encode($snapB->state_vector)
            );

            if (!$result['ok']) {
                throw new \Exception("Simulation Merge Failed: " . ($result['error_message'] ?? 'Unknown error'));
            }

            $mergedSnap = $result['snapshot'];
            $prime->state_vector = is_string($mergedSnap['state_vector']) 
                ? json_decode($mergedSnap['state_vector'], true) 
                : $mergedSnap['state_vector'];
            
            $prime->save();

            // 3. Record Convergence Event
            BranchEvent::create([
                'universe_id' => $prime->id,
                'from_tick' => $prime->current_tick,
                'event_type' => 'convergence',
                'description' => "Timeline synthesis of [{$uA->id}] and [{$uB->id}] into Prime Timeline.",
                'payload' => [
                    'source_a' => $uA->id,
                    'source_b' => $uB->id,
                ]
            ]);

            Log::info("Timeline Merge Successful: Universe [{$prime->id}] created.");

            return $prime;
        });
    }
}

