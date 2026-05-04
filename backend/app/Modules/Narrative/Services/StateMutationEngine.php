<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Simulation\Contracts\UniverseRepositoryInterface;
use App\Modules\Simulation\Entities\UniverseEntity;
use Illuminate\Support\Facades\Log;

/**
 * StateMutationEngine: Deterministically applies narrative signals back to the simulation state.
 */
class StateMutationEngine
{
    public function __construct(
        protected UniverseRepositoryInterface $universeRepository
    ) {}

    /**
     * @param UniverseEntity $universe The entity to mutate
     * @param array<string, float> $impacts Key-value pairs of attribute => delta (e.g., ['entropy' => 0.02])
     */
    public function apply(UniverseEntity $universe, array $impacts): void
    {
        $modified = false;
        
        foreach ($impacts as $key => $delta) {
            // Support both snake_case (AI output) and camelCase (Entity properties)
            $property = $this->resolveProperty($key);
            
            if ($property && property_exists($universe, $property)) {
                $oldValue = $universe->$property;
                $universe->$property = max(0.0, min(1.0, $universe->$property + (float) $delta));
                
                if ($oldValue != $universe->$property) {
                    $modified = true;
                    Log::debug("StateMutationEngine: Updated {$property} by {$delta} (from {$oldValue} to {$universe->$property})");
                }
            }
        }
        
        if ($modified) {
            $this->universeRepository->save($universe);
        }
    }

    protected function resolveProperty(string $key): ?string
    {
        $map = [
            'entropy' => 'entropy',
            'stability' => 'stabilityIndex',
            'stability_index' => 'stabilityIndex',
            'coherence' => 'structuralCoherence',
            'structural_coherence' => 'structuralCoherence',
        ];

        return $map[$key] ?? null;
    }
}
