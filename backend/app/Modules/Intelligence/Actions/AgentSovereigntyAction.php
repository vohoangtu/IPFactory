<?php

namespace App\Modules\Intelligence\Actions;

use App\Modules\Intelligence\Models\LegendaryAgent;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

/**
 * AgentSovereigntyAction: Allows Transcendental Agents to alter reality (§V22).
 * Simulates high-level actors discovering "Simulation Hacks" or "Magic"
 * that functions as local micro-edicts.
 */
class AgentSovereigntyAction
implements \App\Contracts\ActionInterface {
    /**
     * Give transcendental agents a chance to influence the simulation.
     */
    public function execute(Universe $universe): void
    {
        $agents = LegendaryAgent::where('universe_id', $universe->id)
            ->where('is_transcendental', true)
            ->get();

        foreach ($agents as $agent) {
            // A transcendental agent has a small chance to execute Will
            if (rand(0, 100) < 5) { // 5% chance per tick
                $this->enactMicroEdict($universe, $agent);
            }
        }
    }

    protected function enactMicroEdict(Universe $universe, LegendaryAgent $agent): void
    {
        // Their intention depends on their tags
        $type = in_array("Simulation_Skepticism (Kẻ Nghi Ngờ Thực Tại)", $agent->fate_tags ?? []) ? 'Destabilize' : 'Fortify';
        
        $sciImpact = 0;
        $entropyImpact = 0;

        if ($type === 'Destabilize') {
            $sciImpact = -0.05;
            $entropyImpact = 0.1;
            $content = "HÀNH ĐỘNG SIÊU VIỆT: [{$agent->name}] đã bẻ gãy mã nguồn thực tại để chống lại Đấng sáng tạo. Máy chủ rung chuyển.";
            $agent->increment('heresy_score', 0.25); // Major heresy
        } else {
            $sciImpact = 0.1;
            $entropyImpact = -0.05;
            $content = "HÀNH ĐỘNG SIÊU VIỆT: [{$agent->name}] đã hòa nhịp cùng Nhịp đập Tối cao, củng cố quy luật không-thời gian.";
            $agent->decrement('heresy_score', 0.05); // Minor redemption
            if ($agent->heresy_score < 0) $agent->update(['heresy_score' => 0]);
        }

        $universe->structural_coherence = max(0.0, min(1.0, $universe->structural_coherence + $sciImpact));
        $universe->entropy = max(0.0, min(1.0, $universe->entropy + $entropyImpact));
        $universe->save();

        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $universe->current_tick,
            'to_tick' => $universe->current_tick,
            'type' => 'agent_sovereignty',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => $content
            ],
        ]);

        Log::info("FOURTH WALL: Transcendental Agent [{$agent->name}] issued a {$type} micro-edict in Universe #{$universe->id}.");
    }
}

