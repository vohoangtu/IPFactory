<?php

namespace App\Modules\Simulation\Core\Concerns;

/**
 * Optional trait for SimulationEngine: declare product/output types (entity types this engine produces or updates).
 * Used by GET worldos/engines API and engine-products command. Default is empty; override in engine when relevant.
 * Keys should match frontend personae sub: actors, factions, civilizations, supreme, integrity, materials, attractors.
 */
trait HasProductTypes
{
    /**
     * Product type keys this engine produces or updates. Empty = not declared.
     *
     * @return string[]
     */
    public function productTypes(): array
    {
        return [];
    }
}
