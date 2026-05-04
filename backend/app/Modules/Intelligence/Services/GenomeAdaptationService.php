<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

/**
 * Phase 39: Holistic Causal Adaptation (The 14-Layer Civilization State Space).
 * This service calculates the "Emergent Target" for the genome based on 14 resonance layers.
 */
class GenomeAdaptationService
{
    /**
     * Calculate and apply adaptation to the target_genome based on 14 layers.
     */
    public function adapt(Universe $universe): void
    {
        $stateVector = $universe->state_vector ?? [];
        $metrics = $universe->metrics ?? [];
        $factions = $stateVector['factions'] ?? [];
        
        // 1. Get current genome as baseline for target calculation
        $currentGenome = $universe->kernel_genome ?? [
            'diffusion_rate' => 0.1,
            'entropy_coefficient' => 1.0,
            'mutation_rate' => 0.05,
            'cohesion_bonus' => 1.0,
            'cognitive_bias' => 1.0
        ];

        $targetGenome = $stateVector['target_genome'] ?? $currentGenome;

        // --- LAYER CALCULATION LOGIC ---

        // Bio & Actor Layer (Layer 1-2)
        $scholarCount = 0; // Simplified for this implementation
        $overallTech = (float)($stateVector['phase_score']['industrial'] ?? 0) + (float)($stateVector['phase_score']['information'] ?? 0);
        
        // Institutional & Power (Layer 6-8)
        $hasEducation = isset($metrics['institutions']['education']);
        $hasLaw = isset($metrics['institutions']['law']);
        
        // Material & Eco (Layer 9-11)
        $materialStress = (float)($metrics['material_stress'] ?? 0.0);
        $ecoHealth = (float)($metrics['ecosystem_health'] ?? 1.0);

        // 2. Adjust Diffusion Rate (Beta)
        // High Information/Industrial tech, Education, and Language complexity increase Diffusion
        $emergentBeta = $currentGenome['diffusion_rate'];
        if ($overallTech > 0.5) $emergentBeta += 0.1;
        if ($hasEducation) $emergentBeta += 0.05;
        $targetGenome['diffusion_rate'] = min(0.9, $emergentBeta);

        // 3. Adjust Entropy Coefficient (Ec)
        // Law decreases Ec, War (Diplomacy) and Chaos increase Ec
        $emergentEc = $currentGenome['entropy_coefficient'];
        if ($hasLaw) $emergentEc -= 0.2;
        if (($metrics['war_intensity'] ?? 0) > 0.5) $emergentEc += 0.5;
        $targetGenome['entropy_coefficient'] = max(0.1, min(5.0, $emergentEc));

        // 4. Adjust Mutation Rate (Mu)
        // Resource Stress and Chaos increase Mutation Rate (Natural Selection)
        $emergentMu = $currentGenome['mutation_rate'];
        if ($materialStress > 0.7) $emergentMu += 0.05;
        if (($metrics['chaos_level'] ?? 0) > 0.6) $emergentMu += 0.1;
        $targetGenome['mutation_rate'] = max(0.01, min(0.5, $emergentMu));

        // 5. Adjust Cohesion Bonus (Cb)
        // Consistent Culture and Meaning field increase Cb
        $emergentCb = $currentGenome['cohesion_bonus'];
        if (($stateVector['cultural_coherence'] ?? 0) > 0.8) $emergentCb += 0.2;
        $targetGenome['cohesion_bonus'] = max(0.1, min(3.0, $emergentCb));

        // 6. Adjust Cognitive Bias (Gb)
        // Ecosystem health and Civilization Phase
        $emergentGb = $currentGenome['cognitive_bias'];
        if ($ecoHealth < 0.4) $emergentGb += 0.3; // Stress increases sensitivity
        $targetGenome['cognitive_bias'] = max(0.1, min(3.0, $emergentGb));

        // Update target_genome in state_vector
        $stateVector['target_genome'] = $targetGenome;
        $universe->state_vector = $stateVector;
        
        // Log subtle shifts
        Log::debug("GENOME: Adaptive target recalculated for Universe #{$universe->id}.");
    }
}

