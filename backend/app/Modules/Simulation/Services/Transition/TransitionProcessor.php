<?php

namespace App\Modules\Simulation\Services\Transition;

use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Services\Transition\Contracts\StateTransformerInterface;
use App\Modules\Simulation\Services\Transition\Contracts\InvariantGuardInterface;

class TransitionProcessor
{
    /** @var StateTransformerInterface[] */
    private array $transformers = [];

    /** @var InvariantGuardInterface[] */
    private array $guards = [];

    private int $maxIterations = 5;

    public function __construct(iterable $transformers, iterable $guards)
    {
        foreach ($transformers as $transformer) {
            $this->transformers[] = $transformer;
        }
        foreach ($guards as $guard) {
            $this->guards[] = $guard;
        }
    }

    /**
     * Apply the transition using Iterative Convergence logic.
     */
    public function process(WorldState $initialState, string $targetPowerSystem): WorldState
    {
        $currentState = $initialState->snapshot();
        $previousState = null;

        for ($i = 0; $i < $this->maxIterations; $i++) {
            $previousState = $currentState->snapshot();

            // Apply all transformers in the pipeline
            foreach ($this->transformers as $transformer) {
                $currentState = $transformer->apply($currentState, $targetPowerSystem);
            }

            // Verify Invariants and potentially corrective actions
            foreach ($this->guards as $guard) {
                $guard->verify($previousState ?? $initialState, $currentState);
            }

            // Check for convergence (if state delta is minimal)
            if ($this->isConverged($previousState, $currentState)) {
                \Illuminate\Support\Facades\Log::info("Transition converged at iteration {$i}");
                break;
            }
        }

        return $currentState;
    }

    /**
     * Determine if the state has converged between iterations.
     */
    private function isConverged(WorldState $previous, WorldState $current): bool
    {
        $diff = $current->getDiff($previous->toArray());
        
        // If there are no monitored field changes, it's converged
        return empty($diff);
    }
}
