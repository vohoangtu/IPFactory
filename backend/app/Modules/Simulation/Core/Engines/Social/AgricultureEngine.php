<?php

namespace App\Modules\Simulation\Core\Engines\Social;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use App\Modules\Simulation\Core\Events\WorldEvent;
use App\Modules\Simulation\Core\Events\WorldEventType;
use Illuminate\Support\Facades\Log;

/**
 * Agriculture Engine.
 * Quản lý sản xuất lương thực và rủi ro nạn đói dựa trên DSL.
 */
final class AgricultureEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function __construct(
        private ?RuleVmService $ruleVm = null
    ) {
        $this->ruleVm = $this->ruleVm ?? \app(RuleVmService::class);
    }

    public function phase(): string
    {
        return 'economy';
    }

    public function name(): string
    {
        return 'agriculture';
    }

    public function priority(): int
    {
        return 11;
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
            'tech_level' => (float) ($vec['tech_level'] ?? 0.1),
            'land_area' => (float) ($vec['land_area'] ?? 1000),
            'population' => (float) ($vec['population'] ?? 100),
            'ecological_stability' => (float) ($vec['ecology']['stability'] ?? 0.8),
            'random_chance' => (new \Random\Randomizer())->getFloat(0, 1),
            'instability_score' => (float) ($state->get('instability_score', 0.0)),
        ];

        try {
            $result = $this->ruleVm->evaluateRawState($rawState, $dsl);
        } catch (\Exception $e) {
            Log::error("AgricultureEngine: DSL evaluation failed: " . $e->getMessage());
            return new EngineResult([], [], []);
        }
        
        $events = [];
        if ($result['ok'] ?? false) {
            foreach ($result['outputs'] ?? [] as $out) {
                if (($out['event_name'] ?? '') === 'FAMINE_OUTBREAK') {
                    $events[] = WorldEvent::create(
                        WorldEventType::FAMINE,
                        $ctx->getUniverseId(),
                        $ctx->getTick(),
                        null,
                        [],
                        $out['metadata']['intensity'] ?? 0.5
                    );
                }
            }
        }

        return new EngineResult($events, [], []);
    }
}
