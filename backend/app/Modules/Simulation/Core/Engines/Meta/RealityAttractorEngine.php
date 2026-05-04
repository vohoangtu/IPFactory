<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use Illuminate\Support\Facades\Log;
use function resource_path;
use function file_get_contents;
use function file_exists;

/**
 * Phase 52: Reality Attractor Engine (Định hướng vi mô thực tại) 🌌🌀
 * 
 * Tính toán "lực hấp dẫn" của các trạng thái vĩ mô (Attractors) dựa trên 
 * sự phân bổ các trường lực trong manifold.
 */
class RealityAttractorEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    public function __construct(
        protected RuleVmService $ruleVm
    ) {}

    public function name(): string
    {
        return 'reality_attractor';
    }

    public function phase(): string
    {
        return 'meta';
    }

    public function priority(): int
    {
        return 19;
    }

    public function tickRate(): int
    {
        return 5;
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $tick = $ctx->getTick();
        $path = resource_path('worldos_rules/simulation/attractors.dsl');
        
        if (!file_exists($path)) {
            Log::warning("RealityAttractorEngine: attractors.dsl not found at {$path}");
            return EngineResult::empty();
        }

        $dsl = file_get_contents($path);

        // 1. Phân tích trạng thái hiện tại so với các Attractors thông qua Rules
        $this->ruleVm->evaluateAndApplyWithDsl($state, $dsl, $tick);

        Log::debug("RealityAttractorEngine: Calculated reality topology at tick {$tick}. Active attractors updated.");

        return EngineResult::empty();
    }
}



