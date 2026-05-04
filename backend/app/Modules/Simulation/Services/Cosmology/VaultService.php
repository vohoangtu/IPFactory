<?php

namespace App\Modules\Simulation\Services\Cosmology;

use App\Modules\World\Models\Universe;
use App\Modules\World\Models\LegacyVault;
use App\Modules\Institutions\Models\InstitutionalEntity;

/**
 * VaultService – Bảo tàng Di sản Vũ trụ
 * 
 * Khi một nền văn minh (Level 7) hoặc một thực thể vĩ đại (Supreme Entity) sụp đổ,
 * VaultService sẽ trích xuất "linh hồn" (essence) của nó và lưu vào LegacyVault.
 */
class VaultService
{
    /**
     * Lưu trữ di sản của một nền văn minh/định chế.
     */
    public function archiveInstitution(InstitutionalEntity $entity, int $tick): LegacyVault
    {
        $legacyData = [
            'type' => $entity->entity_type,
            'ideology' => $entity->ideology_vector,
            'peak_legitimacy' => $entity->legitimacy,
            'influence' => $entity->influence_map,
            'historical_notes' => "Nền văn minh {$entity->entity_type} sụp đổ tại Tick {$tick} do entropy cao.",
        ];

        return LegacyVault::create([
            'world_id' => $entity->universe->world_id,
            'entity_name' => "Ancient " . ucfirst($entity->entity_type),
            'entity_type' => 'civilization',
            'legacy_data' => $legacyData,
            'archived_at_tick' => $tick,
            'impact_score' => $entity->legitimacy * 100,
        ]);
    }

    /**
     * Trích xuất các di sản cổ đại để "gieo mầm" vào một vũ trụ mới.
     */
    public function getSeeds(int $worldId, int $limit = 3): array
    {
        return LegacyVault::where('world_id', $worldId)
            ->orderByDesc('impact_score')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}


