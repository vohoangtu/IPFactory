<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;

/**
 * Phase 43: Institutional Domain Manager.
 * Handles the emergence and impact of social institutions.
 */
class InstitutionManager
{
    public const INST_LAW = 'law';
    public const INST_BUREAUCRACY = 'bureaucracy';
    public const INST_RELIGION = 'religion';
    public const INST_CORPORATION = 'corporation';
    public const INST_GOVERNMENT = 'government';
    public const INST_EDUCATION = 'education';

    public const ALL_INSTITUTIONS = [
        self::INST_LAW, self::INST_BUREAUCRACY, self::INST_RELIGION,
        self::INST_CORPORATION, self::INST_GOVERNMENT, self::INST_EDUCATION
    ];

    /**
     * Calculates institutional presence in a universe based on metadata and state.
     * These act as multipliers for ZoneFieldCalculator.
     */
    public function getInstitutionImpact(Universe $universe): array
    {
        $state = $universe->state_vector ?? [];
        $institutions = $state['institutions'] ?? [];
        
        $impact = array_fill_keys(self::ALL_INSTITUTIONS, 0.0);

        // Logic for emergence based on tech level, entropy, and stability
        $techLevel = $state['phase_score']['industrial'] ?? 0;
        $infoLevel = $state['phase_score']['information'] ?? 0;
        $stability = $universe->structural_coherence ?? 0.5;

        // Law emerges with stability and tech
        $impact[self::INST_LAW] = max(0.0, $stability * 0.5 + $techLevel * 0.3);
        
        // Bureaucracy emerges with complexity
        $impact[self::INST_BUREAUCRACY] = max(0.0, ($techLevel + $infoLevel) * 0.4);

        // Religion integrates Meaning Attractor
        $meaningField = $state['fields']['meaning'] ?? 0.5;
        $impact[self::INST_RELIGION] = max(0.0, $meaningField * 0.6 + (1 - $techLevel) * 0.4);

        // Corporation emerges in Industrial/Information phases
        $impact[self::INST_CORPORATION] = max(0.0, $techLevel * 0.7 + $infoLevel * 0.3);

        // Government emerges with power and order
        $powerField = $state['fields']['power'] ?? 0.5;
        $impact[self::INST_GOVERNMENT] = max(0.0, $powerField * 0.5 + $stability * 0.4);

        // Education emerges with knowledge and stability
        $knowledgeField = $state['fields']['knowledge'] ?? 0.5;
        $impact[self::INST_EDUCATION] = max(0.0, $knowledgeField * 0.6 + $stability * 0.3);

        return $impact;
    }

    /**
     * returns field modifiers based on active institutions.
     */
    public function getFieldModifiers(array $institutionImpacts): array
    {
        $mods = [
            'survival' => 1.0, 'reproduction' => 1.0, 'wealth' => 1.0, 
            'power' => 1.0, 'knowledge' => 1.0, 'meaning' => 1.0, 
            'status' => 1.0, 'belonging' => 1.0, 'entropy_delta' => 0.0
        ];

        // Law: +Stability (Survival), -Conflict (Power)
        $mods['survival'] += $institutionImpacts[self::INST_LAW] * 0.2;
        $mods['power'] *= 1.0 - $institutionImpacts[self::INST_LAW] * 0.1;

        // Bureaucracy: +Belonging, +Entropy (Efficiency loss)
        $mods['belonging'] += $institutionImpacts[self::INST_BUREAUCRACY] * 0.15;
        $mods['entropy_delta'] += $institutionImpacts[self::INST_BUREAUCRACY] * 0.02;

        // Religion: +Meaning, +Belonging
        $mods['meaning'] += $institutionImpacts[self::INST_RELIGION] * 0.4;
        $mods['belonging'] += $institutionImpacts[self::INST_RELIGION] * 0.2;

        // Corporation: +Wealth, +Status
        $mods['wealth'] += $institutionImpacts[self::INST_CORPORATION] * 0.5;
        $mods['status'] += $institutionImpacts[self::INST_CORPORATION] * 0.2;

        // Government: +Power, +Status
        $mods['power'] += $institutionImpacts[self::INST_GOVERNMENT] * 0.4;
        $mods['status'] += $institutionImpacts[self::INST_GOVERNMENT] * 0.3;

        // Education: +Knowledge
        $mods['knowledge'] += $institutionImpacts[self::INST_EDUCATION] * 0.6;

        return $mods;
    }
}

