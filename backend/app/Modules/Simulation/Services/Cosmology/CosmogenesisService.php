<?php

namespace App\Modules\Simulation\Services\Cosmology;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\BranchEvent;
use App\Modules\Simulation\Actions\ForkUniverseAction;
use Illuminate\Support\Facades\Log;

/**
 * CosmogenesisService: Monitors snapshots for Cosmogenesis events 
 * and triggers autonomous branching (§V9).
 */
class CosmogenesisService
{
    public function __construct(
        protected ForkUniverseAction $branchAction
    ) {}

    /**
     * Scan events for Cosmogenesis and trigger branching.
     */
    public function handleEvents(Universe $universe, array $events): void
    {
        foreach ($events as $event) {
            if ($event === 'Cosmogenesis') {
                $this->triggerBirth($universe);
            }
        }
    }

    protected function triggerBirth(Universe $parent): void
    {
        Log::info("COSMOGENESIS: Autonomous branching triggered for Universe {$parent->id}");

        // V9: Autonomous branching with inheritance
        $this->branchAction->execute($parent, (int)$parent->current_tick, [
            'meta' => [
                'reason' => 'cosmogenesis',
                'note' => 'Sinh ra tự phát từ đỉnh cao nhận thức.',
                'inherit_axioms' => true
            ]
        ]);
        
        Log::info("COSMOGENESIS SUCCESS: Child Universe born.");
    }
}



