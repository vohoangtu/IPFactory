<?php

namespace App\Modules\Intelligence\Domain\Culture;

use App\Modules\Intelligence\Domain\Rng\SimulationRng;
use App\Modules\Intelligence\Services\CultureEngine;

/**
 * Inherit culture from parent with mutation (Tier 7 Culture Engine parent–child transmission).
 */
class CultureInheritanceService
{
    /**
     * @return array<string, float>|null
     */
    public function inherit(?array $parentCulture, float $mutationRate, SimulationRng $rng): ?array
    {
        $dims = CultureEngine::MEME_DIMENSIONS;
        if (!is_array($parentCulture) || empty($parentCulture)) {
            return null;
        }
        $out = [];
        foreach ($dims as $d) {
            $v = max(0.0, min(1.0, (float) ($parentCulture[$d] ?? 0.5)));
            $delta = ($rng->nextFloat() * 2 - 1) * $mutationRate;
            $out[$d] = max(0.0, min(1.0, $v + $delta));
        }
        return $out;
    }
}
