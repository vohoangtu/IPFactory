<?php

namespace App\Modules\Intelligence\Services;

class ArchetypeResolverService
{
    /**
     * Universal pool available in every world.
     */
    private const UNIVERSAL_POOL = [
        'Người Thường'    => 1.5, 
        'Người Sản Xuất' => 1.0, 
        'Thương Nhân'    => 0.4, 
        'Học Giả'       => 0.2, 
        'Hành Giả'      => 0.1, 
        'Chiến Binh'    => 0.3,
    ];

    /**
     * Resolves an archetype probabilistically from the pool.
     */
    public function resolve(
        array $worldAxiom,
        float $worldEntropy = 0.5,
        float $worldStability = 0.5,
        ?\App\Modules\Intelligence\Domain\Rng\SimulationRng $rng = null
    ): string {
        $pool = self::UNIVERSAL_POOL;

        $techLevel = $worldAxiom['tech_level'] ?? 1;

        if ($techLevel >= 3) {
            $pool['Kỹ Sư'] = 0.2;
        }

        if ($worldStability > 0.6) {
            $pool['Hộ Vệ'] = 0.15;
            $pool['Lãnh Đạo'] = 0.05;
        }

        $pool['Tu Sĩ'] = 0.15;
        $pool['Tín Đồ'] = 0.15;
        
        if ($worldEntropy > 0.6) {
            $pool['Kẻ Phá Bĩnh'] = 0.1;
        }

        // Weight modifier by state
        if ($worldEntropy > 0.7) {
            if (isset($pool['Tà Tu'])) $pool['Tà Tu'] *= 2;
            if (isset($pool['Chiến Binh'])) $pool['Chiến Binh'] *= 2;
        } elseif ($worldEntropy < 0.3) {
            if (isset($pool['Học Giả'])) $pool['Học Giả'] *= 1.5;
            if (isset($pool['Thương Nhân'])) $pool['Thương Nhân'] *= 1.5;
        }

        if ($worldStability < 0.4) {
             if (isset($pool['Lãnh Đạo'])) $pool['Lãnh Đạo'] *= 2;
        }

        return $this->selectFromWeightedPool($pool, $rng);
    }

    /**
     * Select a random key from a weighted array.
     * Uses deterministic SimulationRng when available, falls back to rand().
     */
    private function selectFromWeightedPool(
        array $pool,
        ?\App\Modules\Intelligence\Domain\Rng\SimulationRng $rng = null
    ): string {
        $totalWeight = array_sum($pool);

        $randValue = $rng
            ? $rng->nextFloat() * $totalWeight
            : (rand(0, 10000) / 10000) * $totalWeight;
        
        $cumulative = 0;
        foreach ($pool as $archetype => $weight) {
            $cumulative += $weight;
            if ($randValue <= $cumulative) {
                return $archetype;
            }
        }

        return array_key_first($pool);
    }
}
