<?php

namespace App\Modules\Simulation\Services\Cosmology;

use Illuminate\Support\Facades\File;

/**
 * AxiomRegistry provides access to the formal RuleSet Axioms.
 */
class AxiomRegistry
{
    private array $axioms;

    public function __construct()
    {
        $path = app_path('Modules/Simulation/Data/axioms.json');
        $this->axioms = File::exists($path) ? json_decode(File::get($path), true)['axioms'] : [];
    }

    /**
     * Get all axioms in the registry.
     */
    public function getAll(): array
    {
        return $this->axioms;
    }

    /**
     * Find a specific axiom by ID.
     */
    public function find(string $id): ?array
    {
        foreach ($this->axioms as $axiom) {
            if ($axiom['id'] === $id) {
                return $axiom;
            }
        }
        return null;
    }

    /**
     * Get axioms for a specific dimension (e.g., 'physics', 'metaphysics').
     */
    public function getByDimension(string $dimension): array
    {
        return array_values(array_filter($this->axioms, fn($a) => $a['dimension'] === $dimension));
    }

    /**
     * Get axioms available up to a certain tier level.
     */
    public function getByMaxTier(int $tier): array
    {
        return array_values(array_filter($this->axioms, fn($a) => $a['tier'] <= $tier));
    }

    /**
     * Get default values for a set of dimensions at a specific tier.
     */
    public function getDefaultMapForTier(int $tier): array
    {
        $map = [];
        foreach ($this->axioms as $axiom) {
            if ($axiom['tier'] <= $tier) {
                $dimension = $axiom['dimension'];
                $key = str_replace($dimension . '.', '', $axiom['id']);
                
                if (!isset($map[$dimension])) {
                    $map[$dimension] = [];
                }
                
                $map[$dimension][$key] = $axiom['default_value'];
            }
        }
        return $map;
    }
}
