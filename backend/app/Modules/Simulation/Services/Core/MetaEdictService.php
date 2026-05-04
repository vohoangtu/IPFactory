<?php

namespace App\Modules\Simulation\Services\Core;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use Illuminate\Support\Facades\Log;

class MetaEdictService
{
    /**
     * Thêm một Meta-Edict vào World.
     */
    public function addMetaEdict(World $world, string $edictId, string $decreedBy): void
    {
        $axiom = $world->axiom ?? [];
        $metaEdicts = $axiom['meta_edicts'] ?? [];

        if (isset($metaEdicts[$edictId])) {
            return;
        }

        $metaEdicts[$edictId] = [
            'id' => $edictId,
            'decreed_by' => $decreedBy,
            'activated_at' => now()->toIso8601String(),
            'is_immortal' => true
        ];

        $axiom['meta_edicts'] = $metaEdicts;
        $world->axiom = $axiom;
        $world->save();

        Log::info("Meta-Edict Activated for World [{$world->name}]: {$edictId} by {$decreedBy}");
    }

    /**
     * Thu hồi một Meta-Edict.
     */
    public function revokeMetaEdict(World $world, string $edictId): void
    {
        $axiom = $world->axiom ?? [];
        $metaEdicts = $axiom['meta_edicts'] ?? [];

        if (isset($metaEdicts[$edictId])) {
            unset($metaEdicts[$edictId]);
            $axiom['meta_edicts'] = $metaEdicts;
            $world->axiom = $axiom;
            $world->save();
            Log::info("Meta-Edict Revoked for World [{$world->name}]: {$edictId}");
        }
    }

    /**
     * Tiêm các Meta-Edicts vào trạng thái của Universe.
     */
    public function applyToUniverse(Universe $universe, array &$stateVector): void
    {
        $world = $universe->world;
        $axiom = $world->axiom ?? [];
        $metaEdicts = $axiom['meta_edicts'] ?? [];

        if (empty($metaEdicts)) return;

        $universeDiplomacy = $stateVector['active_meta_laws'] ?? [];
        
        foreach ($metaEdicts as $id => $data) {
            $universeDiplomacy[$id] = $data;
        }

        $stateVector['active_meta_laws'] = $universeDiplomacy;
    }
}


