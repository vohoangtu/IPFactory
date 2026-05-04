<?php

namespace App\Modules\Intelligence\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Services\CollectiveUnconsciousService;
use App\Modules\Intelligence\Events\CollectiveUnconsciousShifted;
use Illuminate\Support\Facades\Log;

/**
 * Action to update the Collective Unconscious field in a Universe's state_vector.
 * Phase 80: Mind Layer - RULE_ATTRACTION.
 */
class UpdateCollectiveUnconsciousAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        private readonly CollectiveUnconsciousService $service
    ) {}

    public function execute(Universe $universe): void
    {
        $oldVector = $universe->state_vector['collective_unconscious'] ?? [];
        $newVector = $this->service->calculate($universe);

        $stateVector = $universe->state_vector ?? [];
        $stateVector['collective_unconscious'] = $newVector;
        $universe->state_vector = $stateVector;
        $universe->save();

        if ($this->shouldFireEvent($oldVector, $newVector)) {
            event(new CollectiveUnconsciousShifted($universe, $oldVector, $newVector));
            Log::alert("COLLECTIVE SHIFT: Universe #{$universe->id} mental field has significantly changed.");
        }
    }

    /**
     * Pure calculation: used by runtime systems following effect-first patterns.
     */
    public function calculate(Universe $universe): array
    {
        return $this->service->calculate($universe);
    }

    /**
     * Public wrapper so runtime systems can decide whether to mutate.
     */
    public function shouldMutate(array $old, array $new): bool
    {
        return $this->shouldFireEvent($old, $new);
    }

    private function shouldFireEvent(array $old, array $new): bool
    {
        if (empty($old)) {
            return true;
        }

        $threshold = 0.05;
        foreach ($new as $dim => $value) {
            $oldValue = $old[$dim] ?? 0.0;
            if (abs($value - $oldValue) > $threshold) {
                return true;
            }
        }

        return false;
    }
}


