<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\Causality\CausalLink;
use App\Contracts\CausalityGraphServiceInterface;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

/**
 * Phase 55: Causal History & Reasoning Engine (V8 Core) 🕰️🔗
 * 
 * Engine này phân tích các biến động của State (Deltas) và các Áp lực (Pressures) 
 * để thiết lập các liên kết nhân quả giữa các sự kiện trong Causal Graph.
 */
class CausalHistoryEngine implements SimulationEngine
{
    public function version(): string { return '1.0.0'; }
    public function isParallelSafe(): bool { return true; }
    public function priorityCategory(): string { return 'meta'; }

    public function __construct(
        protected CausalityGraphServiceInterface $causalityGraph
    ) {}

    public function name(): string
    {
        return 'causal_history';
    }

    public function phase(): string
    {
        return 'meta';
    }

    public function priority(): int
    {
        return 16;
    }

    public function tickRate(): int
    {
        return 5;
    }

    /**
     * Record a direct semantic causal link from a system.
     */
    public function recordLink(CausalLink $link, int $tick): void
    {
        // We bridge the semantic link to the low-level CausalityGraph
        // This makes the link permanent and queryable by AI/History UI.
        $this->causalityGraph->recordRelation(
            $link->sourceType . ':' . $link->sourceId,
            $link->relation,
            $link->targetType . ':' . $link->targetId,
            $tick,
            $link->metadata
        );

        Log::debug("CausalHistoryEngine: Recorded semantic link", [
            'src' => $link->sourceType . ':' . $link->sourceId,
            'rel' => $link->relation,
            'tgt' => $link->targetType . ':' . $link->targetId,
        ]);
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $tick = $ctx->getTick();
        $activeAttractor = $state->getActiveAttractor();
        $previousAttractor = $state->getPreviousAttractor();
        $pressures = $state->getPressures();

        // 1. Phân tích sự chuyển dịch Attractor
        if ($activeAttractor !== $previousAttractor && $previousAttractor !== 'none') {
            $this->recordAttractorCausality($state, $previousAttractor, $activeAttractor, $tick);
            // Sau khi ghi nhận, reset previous để tránh ghi đè
            $state->setPreviousAttractor($activeAttractor);
        }

        // 2. Phân tích các áp lực đột biến (Pressure Spikes)
        foreach ($pressures as $name => $value) {
            if ($value > 0.8) {
                // Ghi nhận áp lực cao như một "nguyên nhân tiềm năng" cho các sự kiện tiếp theo
                Log::debug("CausalHistoryEngine: Detecting high pressure spike: {$name} at {$value}");
            }
        }

        return EngineResult::empty();
    }

    protected function recordAttractorCausality(WorldState $state, string $from, string $to, int $tick): void
    {
        $universeId = (int)$state->get('universe_id');
        $id = "TRANSITION_" . $tick . "_" . $from . "_TO_" . $to;
        
        // Ghi lại sự kiện chuyển dịch vào Graph nhân quả
        $this->causalityGraph->recordEvent(
            $universeId,
            $id,
            'ATTRACTOR_TRANSITION',
            $tick
        );

        Log::info("CausalHistoryEngine: Recorded transition causality from {$from} to {$to}");
    }
}
