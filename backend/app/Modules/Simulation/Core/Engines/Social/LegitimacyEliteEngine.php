<?php
namespace App\Modules\Simulation\Core\Engines\Social;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Events\WorldEvent;
use App\Modules\Simulation\Core\Events\WorldEventType;

/**
 * LegitimacyEliteEngine — Tính chính danh và quyền lực giới tinh hoa.
 *
 * Legitimacy giảm khi inequality cao, tăng khi stability tốt.
 * Emit STABILITY_CRISIS khi legitimacy quá thấp.
 */
class LegitimacyEliteEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string { return 'legitimacy_elite'; }
    public function phase(): string { return 'politics'; }
    public function priority(): int { return 11; }
    public function tickRate(): int { return 1; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $result = new EngineResult();
        $tick   = $ctx->getTick();

        if ($tick % 25 !== 0) { return $result; }

        $gini      = (float) $state->get('economy.gini', 0.3);
        $stability = (float) $state->get('politics.stability', 0.7);
        $prevLegitimacy = (float) $state->get('politics.legitimacy', 0.7);

        // Legitimacy erodes with inequality, strengthens with stability
        $baseLegitimacy = 0.8;
        $inequalityPenalty = $gini * 0.4;
        $stabilityBonus    = $stability * 0.3;

        $legitimacy = max(0.0, min(1.0, $baseLegitimacy - $inequalityPenalty + $stabilityBonus - 0.1));

        // Inertia: legitimacy changes slowly
        $legitimacy = $prevLegitimacy * 0.7 + $legitimacy * 0.3;
        $legitimacy = max(0.0, min(1.0, $legitimacy));

        // Elite power: inversely correlated with legitimacy when governance is authoritarian
        $governance = $state->get('politics.governance_type', 'tribal');
        $elitePower = match ($governance) {
            'monarchy', 'chiefdom' => max(0.3, 1.0 - $legitimacy * 0.5),
            'republic'             => max(0.1, 0.5 - $legitimacy * 0.3),
            default                => 0.2,
        };

        $result->stateChanges[] = ['politics' => array_merge(
            $state->get('politics', []),
            [
                'legitimacy' => round($legitimacy, 4),
                'elite_power' => round($elitePower, 4),
            ]
        )];

        if ($legitimacy < 0.2) {
            $result->events[] = WorldEvent::create(
                type: WorldEventType::STABILITY_CRISIS,
                universeId: $ctx->getUniverseId(),
                tick: $tick,
                payload: ['legitimacy' => round($legitimacy, 4), 'trigger' => 'legitimacy_collapse'],
                impactScore: 0.9
            );
        }

        return $result;
    }
}
