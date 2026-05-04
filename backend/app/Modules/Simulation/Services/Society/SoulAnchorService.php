<?php

namespace App\Modules\Simulation\Services\Society;

use App\Modules\Intelligence\Models\LegendaryAgent;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

/**
 * SoulAnchorService: Implements Narrative Continuity (§V22).
 * Allows Transcendental Agents to survive the collapse of their universe.
 */
class SoulAnchorService
{
    /**
     * Anchor souls from a dying universe.
     */
    public function anchorSouls(Universe $dyingUniverse): void
    {
        $transcendentalAgents = LegendaryAgent::where('universe_id', $dyingUniverse->id)
            ->where('is_transcendental', true)
            ->get();

        foreach ($transcendentalAgents as $agent) {
            // Anchor them to the 'Void' (World level) temporarily
            // Here we just mark their universe as null, conceptually putting them in transit
            $agent->update([
                'universe_id' => null,
                'soul_metadata' => array_merge($agent->soul_metadata ?? [], [
                    'last_universe_id' => $dyingUniverse->id,
                    'anchored_at_tick' => $dyingUniverse->world?->global_tick ?? 0
                ])
            ]);
            
            Log::info("SOUL ANCHOR: Legend [{$agent->name}] has transcended the collapse of Universe #{$dyingUniverse->id}.");
        }
    }

    /**
     * Reincarnate anchored souls into a new, stable universe.
     */
    public function reincarnateSouls(Universe $stableUniverse): void
    {
        // Find agents in transit in this world
        $anchoredAgents = LegendaryAgent::whereNull('universe_id')
            ->where('is_transcendental', true)
            ->whereHas('alignment', function ($query) use ($stableUniverse) {
                // Not strictly necessary, but good for structural integrity
            }) // Removing the alignment check for simplicity in finding souls
            ->get(); // In a real scenario, we'd filter by world_id if agents were tied to worlds

        // In this architecture, legends don't have a direct world_id, so we assume all in transit can be placed anywhere stable.
        
        foreach ($anchoredAgents as $agent) {
            $agent->update(['universe_id' => $stableUniverse->id]);
            
            Log::info("REINCARNATION: Transcendental Legend [{$agent->name}] has been reborn in Universe #{$stableUniverse->id}.");
            
            \App\Modules\Narrative\Models\Chronicle::create([
                'universe_id' => $stableUniverse->id,
                'from_tick' => $stableUniverse->current_tick,
                'to_tick' => $stableUniverse->current_tick,
                'type' => 'divine_reincarnation',
                'raw_payload' => [
                'action' => 'legacy_event',
                'description' => "TÁI SINH THẦN THÁNH: Legend [{$agent->name}] đã vượt qua sự sụp đổ của thế giới cũ và hiện diện tại thực tại mới."
            ],
            ]);
        }
    }
}

