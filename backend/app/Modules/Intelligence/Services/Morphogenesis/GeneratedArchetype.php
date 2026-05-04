<?php

namespace App\Modules\Intelligence\Services\Morphogenesis;

use App\Modules\Intelligence\Entities\Contracts\ActorArchetypeInterface;
use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;

/**
 * A data-driven archetype instantiated from a Genome.
 */
class GeneratedArchetype implements ActorArchetypeInterface
{
    public function __construct(
        private ArchetypeGenome $genome
    ) {}

    public function getName(): string
    {
        return $this->genome->name;
    }

    public function getAttractorVector(): array
    {
        return $this->genome->attractorVector;
    }

    public function isEligible(World $world): bool
    {
        // For simplicity in Phase 2, generated archetypes are always eligible
        // unless they have a specific eligibility condition in metadata.
        return true;
    }

    public function getBaseUtility(array $civilizationState): float
    {
        $utility = 0.0;
        $attractor = $this->genome->attractorVector;

        foreach ($attractor as $key => $weight) {
            $val = $civilizationState[$key] ?? 0.0;
            $utility += $val * $weight;
        }

        return $utility;
    }

    public function applyImpact(Universe $universe, UniverseSnapshot $snapshot, array $winnerAgent): array
    {
        // Returns impact events based on the impact vector.
        // For Phase 2, we return a specialized event that modifies the next state.
        return [
            new \App\Modules\Intelligence\Events\ArchetypeImpactEvent(
                $this->getName(),
                $this->genome->impactVector
            )
        ];
    }

    public function getGenome(): ArchetypeGenome
    {
        return $this->genome;
    }
}

