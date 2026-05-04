<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\Intelligence\Entities\ActorState;
use App\Modules\Intelligence\Domain\Society\SocialField;
use App\Modules\Intelligence\Domain\Rng\SimulationRng;
use App\Modules\Intelligence\Domain\Entropy\EntropyBudget;

class CognitiveDynamicsEngine
{
    /**
     * Update actor traits based on cognitive dynamics (social pull, damping, and stochastic noise).
     */
    /**
     * Update actor traits based on cognitive dynamics (social pull, damping, and stochastic noise).
     * Also checks for and applies Cognitive Attractor (Radical State) transitions.
     */
    public function update(
        ActorState $actor,
        SocialField $field,
        SimulationRng $rng,
        EntropyBudget $budget, // Will be used when budget dictates action rate
        float $fieldInfluence = 0.02
    ): ActorState {
        if (!$actor->isAlive) {
            return $actor;
        }

        $traits = $actor->traits;
        
        foreach ($traits as $key => $val) {
            $targetField = $this->mapTraitToField($key, $field);
            
            // Xã hội kéo (Social Pull)
            $pull = ($targetField - $val) * $fieldInfluence; 
            
            // Logistic damping 
            $damping = pow($val, 2) * 0.05; 
            
            // Stochastic element using Deterministic RNG [-0.01, 0.01]
            $noise = ($rng->nextFloat() * 2 - 1) * 0.01; 
            
            $delta = $pull - $damping + $noise;
            
            $traits[$key] = max(0.0, min(1.0, $val + $delta));
        }

        $metrics = $actor->metrics;
        $this->updateRadicalState($traits, $metrics);

        return $actor->with([
            'traits' => $traits,
            'metrics' => $metrics
        ]);
    }

    /**
     * Checks if actor falls into a Radical Basin. Part III.2 feature.
     */
    private function updateRadicalState(array $traits, array &$metrics): void
    {
        $dom = $traits['Dominance'] ?? 0.0;
        $curiosity = $traits['Curiosity'] ?? 0.0;

        $diff = abs($dom - $curiosity);

        $currentState = $metrics['cognitive_state'] ?? null;
        $consecutiveCycles = $metrics['radical_consecutive_cycles'] ?? 0;

        // Check for Radical exit condition
        if ($currentState !== null) {
            if ($diff < 0.2) {
                $consecutiveCycles++;
                if ($consecutiveCycles >= 5) {
                    $metrics['cognitive_state'] = null; // Exit radical state
                    $metrics['radical_intensity'] = 0.0;
                    $consecutiveCycles = 0;
                }
            } else {
                $consecutiveCycles = 0; // Broke the exit streak
                // Determine intensity scale (0.4 to 1.0 mapped to 0-1)
                $metrics['radical_intensity'] = min(1.0, max(0.0, ($diff - 0.4) / 0.6));
            }
        } 
        // Check for Radical entry condition
        else {
            if ($diff > 0.4) {
                $consecutiveCycles++;
                if ($consecutiveCycles >= 3) {
                    $metrics['cognitive_state'] = ($dom > $curiosity) ? 'radical_warrior' : 'radical_scholar';
                    $metrics['radical_intensity'] = min(1.0, max(0.0, ($diff - 0.4) / 0.6));
                    $consecutiveCycles = 0;
                }
            } else {
                $consecutiveCycles = 0;
            }
        }

        $metrics['radical_consecutive_cycles'] = $consecutiveCycles;
    }
    
    /**
     * Maps an individual trait to its corresponding social field value.
     */
    private function mapTraitToField(string $trait, SocialField $field): float
    {
        return match($trait) {
            'Dominance', 'Vengeance', 'Coercion' => $field->aggressionField,
            'Curiosity', 'Pragmatism', 'Ambition' => $field->rationalField,
            'Hope', 'Dogmatism', 'Fear', 'Grief' => $field->spiritualField,
            'Conformity', 'Solidarity', 'Loyalty', 'Empathy', 'Pride', 'Shame' => $field->conformityField,
            default => 0.5 // Neutral pull for unknown traits
        };
    }
}
