<?php

namespace App\Modules\World\Services;

use App\Modules\World\Models\Material;
use App\Modules\Simulation\Models\MaterialReaction;
use Illuminate\Support\Str;

class MaterialService
{
    /**
     * Inject a synthesized material into the simulation as a new Material + Reaction.
     */
    public function injectSynthesizedMaterial(array $data, ?Material $parent = null): Material
    {
        // 1. Create or update the Material
        $slug = Str::slug($data['name']);
        $material = Material::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $data['name'],
                'ontology' => strtolower($data['ontology'] ?? 'physical'),
                'description' => $data['description'] ?? '',
                'pressure_coefficients' => $data['pressure_coefficients'] ?? [],
            ]
        );

        // 2. Create a Reaction from Parent if applicable
        if ($parent) {
            MaterialReaction::updateOrCreate(
                ['slug' => "synthesis-{$parent->slug}-to-{$slug}"],
                [
                    'name' => "Synthesis: {$parent->name} -> {$data['name']}",
                    'inputs' => [$parent->slug => 1],
                    'outputs' => [$slug => 1],
                    'condition' => 'rule "auto_synthesis" when innovation > 0.5 then emit "REACTION_TRIGGERED" end',
                    'rate' => 0.05,
                    'energy_cost' => 5.0
                ]
            );
        }

        return $material;
    }
}
