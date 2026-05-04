<?php

namespace App\Modules\Simulation\Vocation\Services;

use App\Modules\Simulation\Vocation\Entities\SkillEntity;

/**
 * ElementInteractionService: Prepares elemental data for the Vocation Engine.
 * In V1 gRPC-first, core math and emergent effects are moved to Rust DSL.
 */
class ElementInteractionService
{
    protected array $baseElements = ['metal', 'water', 'wood', 'fire', 'earth'];

    /**
     * Prepares elemental field data for DSL context.
     */
    public function getElementData(string $actorId, SkillEntity $skill, array $worldContext): array
    {
        // 1. Get raw fields (mocking actor/world for now)
        $actorField = $worldContext['actor_element_field'] ?? [];
        $skillField = $skill->element; // Use Entity property: element
        $worldField = $worldContext['element_field'] ?? [];

        return [
            'actor_field' => $this->normalize($actorField),
            'skill_field' => $this->normalize($skillField),
            'world_field' => $this->normalize($worldField),
        ];
    }

    /**
     * Calculate basic resonance for simpler DSLs (optional helper).
     */
    public function calculateResonance(string $actorId, SkillEntity $skill, array $worldContext): float
    {
        $data = $this->getElementData($actorId, $skill, $worldContext);
        return $this->dot($data['actor_field'], $data['skill_field']);
    }

    protected function normalize(array $field): array
    {
        $sum = array_sum($field);
        if ($sum <= 0) {
            return array_fill_keys($this->baseElements, 0.0);
        }

        $normalized = [];
        foreach ($this->baseElements as $el) {
            $normalized[$el] = ($field[$el] ?? 0.0) / $sum;
        }
        return $normalized;
    }

    protected function dot(array $a, array $b): float
    {
        $sum = 0.0;
        foreach ($this->baseElements as $el) {
            $sum += ($a[$el] ?? 0.0) * ($b[$el] ?? 0.0);
        }
        return $sum;
    }
}
