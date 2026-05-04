<?php

namespace App\Modules\Intelligence\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Institutions\Services\WorldEdictEngine;
use App\Modules\Intelligence\Services\AI\AnalyticalAiService;
use Illuminate\Support\Facades\Log;

/**
 * OvermindEvolutionAction: Autonomous evolution manager (§V10).
 * Analyzes snapshots and issues edicts to steer the world.
 */
class OvermindEvolutionAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected WorldEdictEngine $edictEngine,
        protected AnalyticalAiService $ai
    ) {}

    /**
     * Analyze and evolve a universe autonomously.
     */
    public function execute(Universe $universe): void
    {
        if (!$universe->world || !$universe->world->is_autonomic) return;

        $latest = $universe->snapshots()->orderByDesc('tick')->first();
        if (!$latest) return;

        // 1. Maintain existing edicts
        $this->edictEngine->decree($universe, $latest);

        // 2. Ask AI for a "Sovereign Direction" if indices are low
        $sci = (float) ($latest->stability_index ?? 1.0);
        $entropy = (float) ($latest->entropy ?? 0.0);

        if ($sci < 0.5 || $entropy > 0.7) {
            $this->issueCorrectionEdicts($universe, $latest, $sci, $entropy);
        }
    }

    protected function issueCorrectionEdicts(Universe $universe, \App\Modules\Simulation\Models\UniverseSnapshot $snapshot, float $sci, float $entropy): void
    {
        Log::info("OVERMIND: Issuing correction edicts for Universe #{$universe->id}");

        $metrics = $snapshot->metrics ?? [];

        // If entropy is high, try to inspire order
        if ($entropy > 0.7) {
            $this->edictEngine->activateEdict($universe, $snapshot->tick, $metrics, 'reiki_revival', 'Overmind', 'Năng lượng bùng nổ vượt ngưỡng, cần khai mở linh khí để ổn định thực tại.');
        }

        // If SCI is low, boost innovation/wisdom to find new stability
        if ($sci < 0.5) {
            $this->edictEngine->activateEdict($universe, $snapshot->tick, $metrics, 'divine_inspiration', 'Overmind', 'Trật tự suy yếu, ý chí tối cao ban xuống sự thông thái để kiến tạo lại thế giới.');
        }

        $snapshot->metrics = $metrics;
        $snapshot->save();
    }
}

