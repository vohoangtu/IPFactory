<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use Illuminate\Support\Facades\Log;

/**
 * Phase 57: Dynamic Metaphysical Axioms Engine (V8+) 🌌📜
 * 
 * Điều phối sự thay đổi của các hằng số vật lý dựa trên trạng thái văn minh (Attractor).
 */
class DynamicLawEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    public function __construct(
        protected RuleVmService $ruleVmService
    ) {}

    public function name(): string
    {
        return 'dynamic_law';
    }

    public function phase(): string
    {
        return 'meta';
    }

    public function priority(): int
    {
        return 17;
    }

    public function tickRate(): int
    {
        return 5;
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $tick = $ctx->getTick();
        
        // Thực thi ma trận dịch chuyển luật vật lý cơ bản
        $this->ruleVmService->evaluateAndApplyWithDsl($state, 'simulation/law_shifts', $tick);

        // Phase 60: Ontological Resonance (Reality Warping)
        $fields = $state->getFields();
        $resonance = (float)($fields['resonance'] ?? 0.0);

        if ($resonance > 0.8) {
            $warpFactor = ($resonance - 0.8) * 5.0; // 0 to 1.0
            $state->set('meta.reality_warping', $warpFactor);
            $this->ruleVmService->evaluateAndApplyWithDsl($state, 'simulation/ontological', $tick);
            Log::info("DynamicLawEngine: Ontological Resonance detected! Warp Factor: $warpFactor");
        }

        Log::debug("DynamicLawEngine: Metaphysical law shifts applied for attractor: " . $state->getActiveAttractor());

        return EngineResult::empty();
    }
}


