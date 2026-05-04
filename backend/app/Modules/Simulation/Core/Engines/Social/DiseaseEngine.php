<?php

namespace App\Modules\Simulation\Core\Engines\Social;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use App\Modules\Simulation\Core\Effects\WorldRulesUpdateEffect;
use App\Modules\Simulation\Core\Events\WorldEvent;
use App\Modules\Simulation\Core\Events\WorldEventType;
use Illuminate\Support\Facades\Log;

/**
 * Disease Engine via DSL (SIR Model).
 * Mô phỏng sự lây lan của dịch bệnh và phản ứng y tế.
 */
final class DiseaseEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function __construct(
        protected ?RuleVmService $ruleVm = null
    ) {
        $this->ruleVm = $this->ruleVm ?? \app(RuleVmService::class);
    }

    public function phase(): string
    {
        return 'ecology';
    }

    public function name(): string
    {
        return 'disease';
    }

    public function priority(): int
    {
        return 14;
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
        $sir = $vec['ecology']['sir_model'] ?? [
            'susceptible' => (float) ($vec['population'] ?? 1000),
            'infected' => 0.0,
            'recovered' => 0.0
        ];

        $rawState = [
            'population' => (float) ($vec['population'] ?? 1000),
            'susceptible' => (float) $sir['susceptible'],
            'infected' => (float) $sir['infected'],
            'recovered' => (float) $sir['recovered'],
            'is_collapse_active' => (bool) ($vec['ecology']['is_collapse_active'] ?? false),
            'collapse_type' => $vec['ecology']['collapse_type'] ?? 'none',
            'mortality' => (float) ($vec['mortality_rate'] ?? 0.02),
        ];

        try {
            $result = $this->ruleVm->evaluateRawState($rawState, $dsl);
        } catch (\Exception $e) {
            Log::error("DiseaseEngine: DSL evaluation failed: " . $e->getMessage());
            return new EngineResult([], [], []);
        }
        
        $effects = [];
        $events = [];

        if ($result['ok'] ?? false) {
            $fs = $result['state'] ?? [];
            
            foreach ($result['outputs'] ?? [] as $out) {
                if (($out['event_name'] ?? '') === 'PANDEMIC_PROGRESS') {
                    $meta = $out['metadata'] ?? [];
                    // Update SIR model locally for this tick's effect
                    $sir['infected'] += (float) ($meta['new_infections'] ?? 0);
                    $sir['infected'] -= (float) ($meta['new_recoveries'] ?? 0);
                    $sir['infected'] -= (float) ($meta['new_deaths'] ?? 0);
                    $sir['susceptible'] -= (float) ($meta['new_infections'] ?? 0);
                    $sir['recovered'] += (float) ($meta['new_recoveries'] ?? 0);
                    
                    // Prevent negatives
                    $sir['infected'] = max(0.0, $sir['infected']);
                    $sir['susceptible'] = max(0.0, $sir['susceptible']);
                    
                    $effects[] = new WorldRulesUpdateEffect([
                        'ecology.sir_model' => $sir,
                        'mortality_rate' => (float) ($fs['mortality'] ?? 0.02)
                    ]);

                    $events[] = WorldEvent::create(
                        WorldEventType::PLAGUE_OUTBREAK,
                        $ctx->getUniverseId(),
                        $ctx->getTick(),
                        null,
                        [],
                        0.5,
                        [],
                        $meta
                    );
                }
            }
        }

        return new EngineResult($events, $effects, []);
    }
}
