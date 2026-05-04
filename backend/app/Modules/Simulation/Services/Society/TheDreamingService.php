<?php

namespace App\Modules\Simulation\Services\Society;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use Illuminate\Support\Facades\Log;
use function resource_path;
use function file_get_contents;

/**
 * TheDreamingService: Manages the subconscious layer of the simulation (§V11).
 * Generates 'Whispers' based on physical tension (Trauma, Entropy) and Narrative resonance.
 */
class TheDreamingService
{
    public function __construct(
        protected \App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService $ruleVm
    ) {}

    /**
     * Generate whispers for a universe based on its latest snapshot.
     */
    public function generateWhispers(Universe $universe): array
    {
        $latest = $universe->snapshots()->orderByDesc('tick')->first();
        if (!$latest) return [];

        // Load Dreaming DSL
        $dsl = @file_get_contents(\resource_path('worldos_rules/simulation/dreaming.dsl')) ?: '';
        
        // Execute via Rule VM
        $result = $this->ruleVm->evaluateRaw($universe, $latest, $dsl);
        
        if (!($result['ok'] ?? false)) return [];

        $outputs = $result['outputs'] ?? [];
        $whispers = [];

        foreach ($outputs as $out) {
            if (($out['type'] ?? '') === 'event') {
                $metadata = $out['metadata'] ?? [];
                $zoneId = $out['scope_id'] ?? null;
                
                $whispers[] = [
                    'zone_id' => $zoneId,
                    'type' => $metadata['type'] ?? 'unknown',
                    'content' => $metadata['content'] ?? 'Dòng chảy tiềm thức đang biến đổi.',
                    'intensity' => (float) ($metadata['intensity'] ?? 0.5)
                ];
            }
        }

        return $whispers;
    }

    /**
     * Calculate Oneric Density for a zone.
     * High density makes the 'physics' soft and receptive to Mythic Resonance.
     */
    public function getOnericDensity(array $zoneState): float
    {
        // Load Dreaming DSL
        $dslFile = \resource_path('worldos_rules/simulation/dreaming.dsl');
        $dsl = @file_get_contents($dslFile) ?: '';
        
        // Evaluate via Rule VM
        $result = $this->ruleVm->evaluateRawState($zoneState, $dsl);
        
        return (float) ($result['state']['oneric_density'] ?? 0.0);
    }
}


