<?php

namespace App\Modules\Simulation\Core\Engines\Social;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use App\Modules\Simulation\Core\Effects\WorldRulesUpdateEffect;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Population Engine via DSL.
 * Quản lý các chỉ số dân số vĩ mô dựa trên tài nguyên và môi trường.
 */
final class PopulationEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function __construct(
        protected ?RuleVmService $ruleVm = null
    ) {
        $this->ruleVm = $ruleVm ?? app(RuleVmService::class);
    }

    public function phase(): string
    {
        return 'ecology';
    }

    public function name(): string
    {
        return 'population';
    }

    public function priority(): int
    {
        return 12;
    }

    public function tickRate(): int
    {
        return 1;
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $dslFile = resource_path('worldos_rules/biology/biosphere.dsl');
        $dsl = @file_get_contents($dslFile) ?: '';
        
        $vec = $state->getStateVector();
        
        $rawState = [
            'population' => (float) ($vec['population'] ?? 1000),
            'entropy' => (float) ($state->get('entropy', 0.5)),
            'is_collapse_active' => (bool) ($vec['ecology']['is_collapse_active'] ?? false),
            'instability_score' => (float) ($vec['ecology']['instability_score'] ?? 0.0),
        ];

        try {
            $result = $this->ruleVm->evaluateRawState($rawState, $dsl);
        } catch (\Exception $e) {
            Log::error("PopulationEngine: DSL evaluation failed: " . $e->getMessage());
            return new EngineResult([], [], []);
        }
        
        $effects = [];
        if ($result['ok'] ?? false) {
            $fs = $result['state'] ?? [];
            $fertility = (float) ($fs['fertility'] ?? 0.05);
            $mortality = (float) ($fs['mortality'] ?? 0.02);
            
            $currentPop = (float) ($vec['population'] ?? 1000);
            $growth = $currentPop * ($fertility - $mortality);
            $newPop = max(0.0, $currentPop + $growth);
            
            // Effect to update population in state vector
            $effects[] = new WorldRulesUpdateEffect([
                'population' => $newPop,
                'last_growth' => $growth,
                'fertility_rate' => $fertility,
                'mortality_rate' => $mortality
            ]);
        }

        return new EngineResult([], $effects, []);
    }
}
