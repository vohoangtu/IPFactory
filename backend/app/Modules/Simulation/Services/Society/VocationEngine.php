<?php

namespace App\Modules\Simulation\Services\Society;

// Removed Eloquent Actor model dependency for Entity compatibility
use Illuminate\Support\Facades\File;

/**
 * VocationEngine manages actor vocation progression and stat calculation.
 * It maps actor achievements and attributes to the available vocation paths
 * defined in the RuleSet.
 */
class VocationEngine
{
    private array $vocations;
    private array $elementMatrix;

    public function __construct()
    {
        $path = app_path('Modules/Simulation/Data/vocations.json');
        if (File::exists($path)) {
            $data = json_decode(File::get($path), true);
            $this->vocations = $data['vocations'] ?? [];
            $this->elementMatrix = $data['element_matrix'] ?? [];
        } else {
            $this->vocations = [];
            $this->elementMatrix = [];
        }
    }

    /**
     * Calculate absolute skill power for an actor.
     * Takes into account: Element Matrix, Resonance, Bloodline, Fate, and Physique.
     */
    public function calculateSkillPower(object $actor, array $skill, ?string $targetElement = null): float
    {
        $power = (float)($skill['effects']['damage'] ?? $skill['effects']['healing'] ?? 100);

        // 1. Element Counter
        if ($targetElement && isset($skill['element'])) {
            $counterMult = $this->elementMatrix[$skill['element']][$targetElement] ?? 1.0;
            $power *= $counterMult;
        }

        // 2. Resonance Rate
        $resonance = $skill['resonance_rate'] ?? 1.0;
        $power *= $resonance;

        // 3. Bloodline Scaling
        if (isset($skill['required_lineage']) && ($actor->lineage_id ?? null) === $skill['required_lineage']) {
            $scaling = $skill['bloodline_scaling'] ?? 1.0;
            $purity = $this->calculateBloodlinePurity($actor);
            $power *= (1 + ($scaling - 1) * $purity);
        }

        // 4. Fate Resonance
        if (isset($skill['fate_resonance'])) {
            $actorFate = $actor->metrics['fate_star'] ?? null;
            if ($actorFate && isset($skill['fate_resonance'][$actorFate])) {
                $power *= $skill['fate_resonance'][$actorFate];
            }
        }

        // 5. Physique Affinity
        if (isset($skill['physique_affinity'])) {
            $actorPhysique = $actor->metrics['physique_type'] ?? null;
            if ($actorPhysique && isset($skill['physique_affinity'][$actorPhysique])) {
                $power *= $skill['physique_affinity'][$actorPhysique];
            }
        }

        // 6. Awakening Mutation
        if ($this->isAwakened($actor, $skill)) {
            $mutation = $skill['awakening_mutation']['enhanced_effects'] ?? [];
            $power *= ($mutation['damage_mult'] ?? 1.0);
        }

        return $power;
    }

    /**
     * Determine if a skill triggers a backfire event.
     */
    public function checkBackfire(object $actor, array $skill): bool
    {
        $baseRisk = $skill['backfire_risk'] ?? 0.0;
        
        // Fatigue increases risk
        $vitality = $actor->vitality ?? [];
        $fatigue = $vitality['fatigue'] ?? 0.0;
        $actualRisk = $baseRisk + ($fatigue * 0.2);

        return (mt_rand(0, 1000) / 1000) < $actualRisk;
    }

    private function calculateBloodlinePurity(object $actor): float
    {
        // Purity is derived from specific pride/ancestral traits (e.g. trait index 15)
        $traits = $actor->traits ?? [];
        return $traits[15] ?? 0.5;
    }

    private function isAwakened(object $actor, array $skill): bool
    {
        if (!isset($skill['awakening_mutation'])) return false;
        
        // Awakening requires both bloodline and high resonance/willpower
        return (($actor->lineage_id ?? null) === $skill['required_lineage']) && 
               (($actor->metrics['willpower'] ?? 0) > 0.85);
    }

    /**
     * Retrieve a specific skill's metadata from a vocation.
     */
    public function getSkill(string $vocationId, string $skillId): ?array
    {
        $vocation = $this->findVocation($vocationId);
        if (!$vocation) return null;

        foreach ($vocation['skills'] ?? [] as $skill) {
            if ($skill['id'] === $skillId) {
                return $skill;
            }
        }
        return null;
    }

    /**
     * Handle skill synthesis attempt.
     */
    public function attemptSynthesis(object $actor, string $vocationId, string $recipeId): ?array
    {
        $vocation = $this->findVocation($vocationId);
        if (!$vocation || !isset($vocation['synthesis_recipes'])) return ['success' => false];

        foreach ($vocation['synthesis_recipes'] as $recipe) {
            if ($recipe['id'] === $recipeId || $recipeId === 'RANDOM_ACCIDENT') {
                if ($this->meetsSynthesisRequirements($actor, $recipe)) {
                    $chance = $recipe['discovery_chance'] ?? 0.1;
                    $stats = $actor->stats ?? [];
                    $bonus = ($stats['intelligence'] ?? 1.0) * 0.05; // Int helps discovery
                    
                    if ((mt_rand(0, 1000) / 1000) < ($chance + $bonus)) {
                        return ['success' => true, 'skill' => $recipe]; // Success!
                    }
                }
            }
        }
        return ['success' => false];
    }

    private function meetsSynthesisRequirements(object $actor, array $recipe): bool
    {
        $ingredients = $recipe['ingredients'] ?? [];
        $capabilities = $actor->capabilities ?? [];
        $actorCapabilities = $capabilities['unlocked_skills'] ?? [];

        foreach ($ingredients as $ing) {
            // If ingredient is a skill, check if actor has it
            if (str_contains($ing, '_') && !in_array($ing, $actorCapabilities)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Calculate updated stats for an actor based on their current vocation.
     */
    public function calculateStats(object $actor): array
    {
        $vocationId = $actor->vocationId ?? $actor->vocation_id ?? 'commoner';
        $vocation = $this->findVocation($vocationId);

        if (!$vocation) {
            return $actor->stats ?? [];
        }

        $baseStats = $actor->stats ?? [];
        $scaling = $vocation['base_stats'] ?? [];

        foreach ($scaling as $stat => $coefficient) {
            if (isset($baseStats[$stat])) {
                $baseStats[$stat] *= $coefficient;
            }
        }

        return $baseStats;
    }

    /**
     * Check if an actor is eligible for a higher-tier vocation.
     */
    public function getEligibleEvolutions(object $actor): array
    {
        $vocationId = $actor->vocationId ?? $actor->vocation_id ?? 'commoner';
        $currentVocation = $this->findVocation($vocationId);
        if (!$currentVocation) {
            return [];
        }

        $eligible = [];
        $possibleEvolutions = $currentVocation['evolves_to'] ?? [];

        foreach ($possibleEvolutions as $vocationId) {
            $vocation = $this->findVocation($vocationId);
            if ($vocation && $this->meetsRequirements($actor, $vocation)) {
                $eligible[] = $vocation;
            }
        }

        return $eligible;
    }

    /**
     * Calculate bonus effects from a sequence of used skills (Combo).
     */
    public function calculateComboBonus(object $actor, array $skillHistory): array
    {
        $vocationId = $actor->vocationId ?? $actor->vocation_id ?? '';
        $vocation = $this->findVocation($vocationId);
        if (!$vocation || !isset($vocation['combos'])) return [];

        foreach ($vocation['combos'] as $combo) {
            $sequence = $combo['sequence'] ?? [];
            if (empty($sequence)) continue;

            // Check if skillHistory ends with the sequence
            $historyCount = count($skillHistory);
            $seqCount = count($sequence);
            
            if ($historyCount >= $seqCount) {
                $lastSkills = array_slice($skillHistory, -$seqCount);
                if ($lastSkills === $sequence) {
                    return $combo['bonus_effects'] ?? [];
                }
            }
        }
        return [];
    }

    public function findVocation(string $id): ?array
    {
        foreach ($this->vocations as $vocation) {
            if ($vocation['id'] === $id) {
                return $vocation;
            }
        }
        return null;
    }

    private function meetsRequirements(object $actor, array $vocation): bool
    {
        $requirements = $vocation['requirements'] ?? [];
        $actorStats = $actor->stats ?? [];
        $actorMetrics = $actor->metrics ?? [];

        foreach ($requirements as $key => $value) {
            $actualValue = $actorStats[$key] ?? $actorMetrics[$key] ?? 0;
            if ($actualValue < $value) {
                return false;
            }
        }

        return true;
    }
}
