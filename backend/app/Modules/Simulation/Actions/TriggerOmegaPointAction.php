<?php

namespace App\Modules\Simulation\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Services\Core\HttpSimulationEngineClient;
use Illuminate\Support\Facades\Log;

/**
 * TriggerOmegaPointAction: The final closure of WorldOS (§V8).
 * Merges all recursive layers into a single singularity.
 */
class TriggerOmegaPointAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected HttpSimulationEngineClient $client
    ) {}

    /**
     * Execute the Omega Point (Singularity).
     * Requirements: Stability < 0.1, Paradox events detected.
     */
    public function execute(Universe $universe): array
    {
        Log::warning("OMEGA POINT TRIGERRED for Universe {$universe->id}");

        // 1. Force a "Crisis" state in all zones
        $content = "ĐIỂM OMEGA: Các tầng đệ quy sụp đổ. Thực tại và Giả lập hòa làm một. " .
                   "Mọi ranh giới bản thể bị xóa nhòa trong một điểm kỳ dị vô hạn.";

        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => (int)$universe->current_tick,
            'to_tick' => (int)$universe->current_tick,
            'type' => 'omega_point',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => $content
            ],
        ]);

        // 2. Technical Singularity: Boost entropy to max and freeze
        $universe->update(['status' => 'singular']);

        return [
            'ok' => true,
            'message' => 'Vũ trụ đã đạt đến điểm Omega. Chu kỳ kết thúc.',
        ];
    }
}


