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
 * Phase 72: Singularity Stability Engine 🛡️🌀
 * 
 * Ngăn chặn các nghịch lý nhân quả và vòng lặp vô tận (Paradox Containment).
 * Đảm bảo thực tại không bị sụp đổ khi logic tự sinh (Autopoietic) biến đổi quá mức.
 */
class SingularityStabilityEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    public function __construct(
        private readonly RuleVmService $vmService
    ) {}

    public function name(): string
    {
        return 'singularity_stability';
    }

    public function phase(): string
    {
        return 'meta';
    }

    public function priority(): int
    {
        return 72;
    }

    public function tickRate(): int
    {
        return 5;
    }

    /**
     * Chạy giám sát và ổn định hóa thực tại.
     */
    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $tick = $ctx->getTick();
        $stability = (float)$state->get('stability_index', 1.0);
        $entropy = (float)$state->get('fields.entropy', 0);
        if ($entropy <= 0) $entropy = (float)$state->get('entropy', 0.1);
        
        $resourceStress = (float)$state->get('fields.resource_stress', 0);
        if ($resourceStress <= 0) $resourceStress = (float)$state->get('resource_stress', 0);
        
        $divergence = (float)$state->get('meta.causal_divergence', 0);

        // 0. Natural Stability Decay under stress
        $decay = ($entropy * 0.05) + ($resourceStress * 0.1);
        $stability = max(0.0, $stability - $decay);
        
        $result = new EngineResult();
        $result->stateChanges[] = ['stability_index' => $stability];
        $state->set('stability_index', $stability); // Keep local sync for RuleVM

        // 1. Phân tích Paradox (Nghịch lý)
        if ($entropy > 0.8 && $stability < 0.3) {
            $paradoxResult = $this->containParadox($state);
            $result->stateChanges = array_merge($result->stateChanges, $paradoxResult->stateChanges);
        }

        // 2. Phân tích Divergence (Phân kỳ)
        if ($divergence > 0.8) {
            $dampeningResult = $this->applyCausalDampening($state);
            $result->stateChanges = array_merge($result->stateChanges, $dampeningResult->stateChanges);
        }

        // 3. Thực thi Apex DSL Rules cho sự ổn định
        $this->vmService->evaluateAndApplyWithDsl(
            $state,
            'simulation/apex.dsl',
            $tick,
            ['mode' => 'STABILITY_CHECK']
        );

        return $result;
    }

    /**
     * Chống lại nghịch lý bằng cách reset các biến động cực đoan.
     */
    private function containParadox(WorldState $state): EngineResult
    {
        Log::warning("SingularityStabilityEngine: Paradox detected! Initiating containment protocols.");
        
        $result = new EngineResult();
        $result->stateChanges[] = ['stability_index' => 0.5];
        $state->updateField('entropy', -0.3, 'Paradox Containment');
        
        // Ghi lại Scar lịch sử
        $scars = $state->get('scars', []);
        $scars[] = [
            'type' => 'PARADOX_CONTAINMENT',
            'magnitude' => 0.8,
            'description' => 'Một nghịch lý nhân quả đã bị cưỡng chế dập tắt bởi hệ thống ổn định Singularity.',
            'timestamp' => now()->toIso8601String()
        ];
        $result->stateChanges[] = ['scars' => $scars];
        
        return $result;
    }

    /**
     * Giảm tốc độ thay đổi của các trường để tránh sụp đổ logic.
     */
    private function applyCausalDampening(WorldState $state): EngineResult
    {
        Log::info("SingularityStabilityEngine: High causal divergence. Applying dampening.");
        
        $result = new EngineResult();
        $mutationRate = (float)$state->get('meta.rule_mutation_rate', 0) * 0.5;
        $divergence = (float)$state->get('meta.causal_divergence', 0) * 0.7;
        
        $result->stateChanges[] = ['meta.rule_mutation_rate' => $mutationRate];
        $result->stateChanges[] = ['meta.causal_divergence' => $divergence];
        
        return $result;
    }
}



