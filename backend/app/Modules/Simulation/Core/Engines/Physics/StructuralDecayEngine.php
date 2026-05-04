<?php

namespace App\Modules\Simulation\Core\Engines\Physics;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Effects\StructuralDecayEffect;
use App\Modules\Simulation\Core\Events\WorldEvent;
use App\Modules\Simulation\Core\Events\WorldEventType;
use App\Modules\Simulation\Core\Support\SimulationRandom;

/**
 * Structural Decay Engine.
 * Ngăn chặn simulation bị đứng yên (equilibrium) bằng cách tiêm entropy.
 */
final class StructuralDecayEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    /** Order above this with entropy below threshold triggers decay */
    private const ORDER_HIGH_THRESHOLD = 0.75;
    /** Entropy below this with order above threshold triggers decay */
    private const ENTROPY_LOW_THRESHOLD = 0.35;
    /** Max entropy injection per tick when decay runs */
    private const ENTROPY_INJECTION = 0.008;
    /** Max order reduction per tick when decay runs */
    private const ORDER_DECAY = -0.005;

    public function name(): string
    {
        return 'structural_decay';
    }

    public function phase(): string
    {
        return 'ecology';
    }

    public function priority(): int
    {
        return 4;
    }

    public function tickRate(): int
    {
        return max(1, (int) (config('worldos.time_scale_factors.structural_decay') ?? 5));
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $rng = new SimulationRandom($ctx->getSeed(), $ctx->getTick(), 0);
        $effects = $this->evaluate($state, $rng);
        $events = [];
        
        if (!empty($effects)) {
            $events[] = WorldEvent::create(
                WorldEventType::STRUCTURAL_DECAY,
                $ctx->getUniverseId(),
                $ctx->getTick(),
                null,
                [],
                0.1,
                [],
                ['trigger' => 'anti_freeze']
            );
        }
        
        return new EngineResult($events, $effects, []);
    }

    /**
     * @return \App\Modules\Simulation\Core\Effects\StructuralDecayEffect[]
     */
    private function evaluate(WorldState $state, SimulationRandom $rng): array
    {
        $entropy = (float) $state->get('entropy', 0.5);
        $vec = $state->getStateVector();
        $order = (float) ($vec['order'] ?? 0.5);

        $tooStable = $entropy < self::ENTROPY_LOW_THRESHOLD && $order > self::ORDER_HIGH_THRESHOLD;
        if (!$tooStable) {
            return [];
        }

        $entropyDelta = self::ENTROPY_INJECTION * (1.0 + $rng->float(-0.2, 0.2));
        $orderDelta = self::ORDER_DECAY * (1.0 + $rng->float(-0.2, 0.2));

        return [new StructuralDecayEffect($entropyDelta, $orderDelta)];
    }
}
