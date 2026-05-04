<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\BranchEvent;
use App\Modules\Simulation\Services\Core\ImplicitOrchestratorService;
use Illuminate\Support\Facades\Log;

class OmegaEngine
{
    public function __construct(
        protected ImplicitOrchestratorService $orchestrator
    ) {}

    /**
     * Kiểm tra và kích hoạt các trạng thái kết thúc (Omega States) (§49.3).
     */
    public function checkOmegaStatus(Universe $universe, array $metrics): void
    {
        $entropy = $metrics['entropy'] ?? 0;
        $sci = $metrics['sci'] ?? 0;
        $tech = $metrics['knowledge_frontier_avg'] ?? 0;
        
        // 1. Heat Death (Entropy cực đại, Trật tự biến mất)
        if ($entropy > 0.98) {
            $this->triggerHeatDeath($universe);
        }

        // 2. Apotheosis (Đỉnh cao văn minh, SCI và Tech tối đa)
        if ($sci > 0.98 && $tech > 0.96) {
            $this->triggerApotheosis($universe);
        }
    }

    protected function triggerHeatDeath(Universe $universe): void
    {
        if ($universe->status === 'halted') return;

        Log::critical("OMEGA STATE: Heat Death detected in Universe [{$universe->id}]. Simulation halted.");
        
        $universe->update(['status' => 'halted']);
        
        BranchEvent::create([
            'universe_id' => $universe->id,
            'from_tick' => $universe->current_tick,
            'event_type' => 'heat_death',
            'description' => "Vũ trụ đã chạm tới Entropy tuyệt đối. Mọi cấu trúc tan rã.",
        ]);

        // Auto-respawn: spawn new universe from the ashes
        try {
            $child = $this->orchestrator->spawnUniverse(
                $universe->world,
                $universe->id,
                $universe->saga_id,
                ['reason' => 'heat_death_respawn', 'mutation' => ['suggest_reduce_entropy' => true]]
            );
            Log::info("OMEGA STATE: Universe [{$universe->id}] respawned as [{$child->id}].");
        } catch (\Throwable $e) {
            Log::error("OMEGA STATE: Failed to respawn universe [{$universe->id}]: {$e->getMessage()}");
        }
    }

    protected function triggerApotheosis(Universe $universe): void
    {
        Log::info("OMEGA STATE: Apotheosis achieved in Universe [{$universe->id}].");

        BranchEvent::create([
            'universe_id' => $universe->id,
            'from_tick' => $universe->current_tick,
            'event_type' => 'apotheosis',
            'description' => "Thăng hoa toàn thể (Collective Ascension). Nền văn minh đã vượt qua giới hạn vật chất.",
            'payload' => ['is_eternal' => true]
        ]);
    }
}

