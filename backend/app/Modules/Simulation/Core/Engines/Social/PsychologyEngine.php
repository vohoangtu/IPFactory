<?php
namespace App\Modules\Simulation\Core\Engines\Social;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use function data_get;

/**
 * PsychologyEngine — Tâm lý tập thể: morale, unrest per zone.
 *
 * Tổng hợp từ resource scarcity, inequality, legitimacy, war_pressure.
 */
class PsychologyEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string { return 'psychology'; }
    public function phase(): string { return 'social'; }
    public function priority(): int { return 5; }
    public function tickRate(): int { return 1; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $result = new EngineResult();
        $tick   = $ctx->getTick();

        if ($tick % 10 !== 0) { return $result; }

        $zones      = $state->getZones();
        $gini       = (float) $state->get('economy.gini', 0.3);
        $legitimacy = (float) $state->get('politics.legitimacy', 0.7);
        $actors     = $state->getActorEntities();

        $updatedZones = [];

        foreach ($zones as $idx => $zone) {
            $s = $zone['state'] ?? [];
            $zoneId = $zone['id'];
            $population = (float) ($s['population'] ?? 0);
            $resource   = (float) ($s['resource'] ?? 0);
            $warPressure = (float) ($s['war_pressure'] ?? 0);

            // 1. Environmental Scarcity
            $need = $population * 2.0;
            $scarcity = $need > 0 ? max(0.0, 1.0 - ($resource / $need)) : 0.0;

            // 2. Individual Psychological Aggregate (Individual -> Collective Feedback)
            $zoneActors = array_filter($actors, fn($a) => $a->zone_id == $zoneId);
            $avgFear    = 0.0;
            $avgAnger   = 0.0;
            $avgJoy     = 0.0;
            
            if (!empty($zoneActors)) {
                foreach ($zoneActors as $actor) {
                    $psych = data_get($actor->metrics, 'psych_state', []);
                    $avgFear  += (float) ($psych['fear'] ?? 0.1);
                    $avgAnger += (float) ($psych['anger'] ?? 0.1);
                    $avgJoy   += (float) ($psych['joy'] ?? 0.1);
                }
                $count    = count($zoneActors);
                $avgFear  /= $count;
                $avgAnger /= $count;
                $avgJoy   /= $count;
            } else {
                // Default baseline if no actors present
                $avgFear = 0.2; $avgAnger = 0.1; $avgJoy = 0.1;
            }

            // 3. Morale calculation
            // Base morale from environment
            $envMorale = 1.0 - $scarcity * 0.3 - $gini * 0.15 - $warPressure * 0.2;
            
            // Adjusted morale by individual psychology
            $morale = ($envMorale * 0.4) + ($avgJoy * 0.4) - ($avgFear * 0.2) - ($avgAnger * 0.1);
            $morale = max(0.0, min(1.0, $morale));

            // 4. Unrest calculation
            $unrest = (1.0 - $morale) * ($gini + $avgAnger) * (1.1 - $legitimacy);
            $unrest = max(0.0, min(1.0, $unrest));

            $s['morale'] = round($morale, 4);
            $s['unrest'] = round($unrest, 4);
            $s['avg_fear'] = round($avgFear, 3);
            $s['avg_anger'] = round($avgAnger, 3);
            
            $zone['state'] = $s;
            $updatedZones[] = $zone;
        }

        if (!empty($updatedZones)) {
            $result->stateChanges[] = ['zones' => $updatedZones];
        }

        return $result;
    }
}
