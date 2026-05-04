<?php

namespace App\Modules\Intelligence\Actions;

use App\Modules\Intelligence\Entities\ActorState;
use App\Modules\Intelligence\Domain\Archetype\ArchetypeClassifier;

class UpdateArchetypeAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        private ArchetypeClassifier $classifier
    ) {}

    public function handle(
        ActorState $actor,
        array $worldAxiom,
        float $entropy,
        array $ratios = [],
        ?\App\Modules\Intelligence\Domain\Phase\PhaseScore $phaseScore = null,
        array $zoneFields = [],
        array $culturalPressure = []
    ): ActorState {
        $newArchetype = $this->classifier->classify($actor, $worldAxiom, $entropy, $ratios, $phaseScore, $zoneFields, $culturalPressure);

        if ($newArchetype !== null && $newArchetype !== $actor->archetype) {
            $metrics = $actor->metrics;
            $metrics['archetype_stable_cycles'] = 0; // Reset
            return $actor->with([
                'archetype' => $newArchetype,
                'metrics' => $metrics
            ]);
        }

        // Increment stable cycles
        $metrics = $actor->metrics;
        $metrics['archetype_stable_cycles'] = ($metrics['archetype_stable_cycles'] ?? 0) + 1;
        
        return $actor->with(['metrics' => $metrics]);
    }
}
