<?php

namespace App\Modules\Intelligence\Services\Lab;

/**
 * Layer 10: Civilization Creator ("God Mode").
 * Allows manual or algorithmic design of completely new civilization forms.
 */
class CivilizationCreator
{
    /**
     * Create a new custom civilization blueprint.
     */
    public function createBlueprint(string $name, array $customState, array $customConfig, array $archetypeGenomes): array
    {
        return [
            'id' => uniqid('blueprint_'),
            'name' => $name,
            'initial_state' => array_merge([
                'knowledge' => 0.1,
                'coercion' => 0.5,
                'stability' => 0.9,
                'stagnation' => 0.0,
                'entropy' => 0.01,
            ], $customState),
            'world_config' => array_merge([
                'mutation_rate' => 0.05,
                'resource_scarcity' => 0.5,
                'climate_volatility' => 0.1,
            ], $customConfig),
            'archetypes' => array_map(fn($g) => $g->toArray(), $archetypeGenomes),
            'created_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Package blueprint to a JSON string for sharing.
     */
    public function exportBlueprint(array $blueprint): string
    {
        return json_encode($blueprint, JSON_PRETTY_PRINT);
    }
}
