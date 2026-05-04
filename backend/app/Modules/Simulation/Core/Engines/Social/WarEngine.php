<?php
namespace App\Modules\Simulation\Core\Engines\Social;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Events\WorldEvent;
use App\Modules\Simulation\Core\Events\WorldEventType;
use function config;


/**
 * WarEngine — Chiến tranh liên vùng.
 *
 * War probability = f(resource_scarcity, power_imbalance, unrest).
 * Battle outcome: power * random. Winner gains territory/resources.
 */
class WarEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string { return 'war'; }
    public function phase(): string { return 'conflict'; }
    public function priority(): int { return 10; }
    public function tickRate(): int { return 1; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $result   = new EngineResult();
        $tick     = $ctx->getTick();
        $interval = (int) config('worldos.war_tick_interval', 30);

        if ($interval <= 0 || $tick % $interval !== 0) { return $result; }

        $zones = $state->getZones();
        $seed  = $ctx->getSeed();

        if (count($zones) < 2) { return $result; }

        // Calculate war probability from global state
        $gini       = (float) $state->get('economy.gini', 0.3);
        $legitimacy = (float) $state->get('politics.legitimacy', 0.7);

        // Find zones with highest power and unrest
        $maxUnrest  = 0.0;
        $maxPower   = 0.0;
        $aggressorIdx = null;
        $targetIdx    = null;

        foreach ($zones as $idx => $zone) {
            $s = $zone['state'] ?? [];
            $unrest  = (float) ($s['unrest'] ?? 0);
            $pop     = (float) ($s['population'] ?? 0);
            $power   = $pop * (1.0 + ($s['infrastructure_level'] ?? 0) * 0.5);

            if ($unrest > $maxUnrest && $pop > 3) {
                $maxUnrest = $unrest;
                $aggressorIdx = $idx;
            }
            if ($power > $maxPower) {
                $maxPower = $power;
            }
        }

        // War probability
        $warProb = $maxUnrest * 0.3 + $gini * 0.3 + (1.0 - $legitimacy) * 0.2;
        $warHash = abs(crc32("war_{$seed}_{$tick}")) / 2147483647;

        if ($warHash > $warProb || $aggressorIdx === null) {
            // No war this tick, just update war_pressure
            $updatedZones = [];
            foreach ($zones as $zone) {
                $s = $zone['state'] ?? [];
                $s['war_pressure'] = round(max(0, ($s['war_pressure'] ?? 0) - 0.05), 4);
                $zone['state'] = $s;
                $updatedZones[] = $zone;
            }
            $result->stateChanges['zones'] = $updatedZones;
            return $result;
        }

        // Find target: neighbor with most resources
        $maxTargetResource = -1;
        foreach ($zones as $idx => $zone) {
            if ($idx === $aggressorIdx) continue;
            $resource = (float) ($zone['state']['resource'] ?? 0);
            if ($resource > $maxTargetResource) {
                $maxTargetResource = $resource;
                $targetIdx = $idx;
            }
        }

        if ($targetIdx === null) { return $result; }

        // Battle
        $aPop  = (float) ($zones[$aggressorIdx]['state']['population'] ?? 1);
        $tPop  = (float) ($zones[$targetIdx]['state']['population'] ?? 1);
        $aRoll = $aPop * (0.8 + (abs(crc32("battle_a_{$seed}_{$tick}")) / 2147483647) * 0.4);
        $tRoll = $tPop * (0.8 + (abs(crc32("battle_t_{$seed}_{$tick}")) / 2147483647) * 0.4);

        $updatedZones = $zones;
        $aState = $updatedZones[$aggressorIdx]['state'];
        $tState = $updatedZones[$targetIdx]['state'];

        $result->events[] = WorldEvent::create(
            type: WorldEventType::WAR_DECLARED,
            universeId: $ctx->getUniverseId(),
            tick: $tick,
            payload: [
                'aggressor' => $updatedZones[$aggressorIdx]['id'] ?? $aggressorIdx,
                'target' => $updatedZones[$targetIdx]['id'] ?? $targetIdx,
            ],
            impactScore: 0.7
        );

        if ($aRoll > $tRoll) {
            // Aggressor wins: steal resources, target loses population
            $loot = min($tState['resource'] ?? 0, $aPop * 0.5);
            $casualties = min($tPop * 0.3, $tPop);

            $aState['resource'] = round(($aState['resource'] ?? 0) + $loot, 2);
            $tState['resource'] = round(max(0, ($tState['resource'] ?? 0) - $loot), 2);
            $tState['population'] = round(max(0, ($tState['population'] ?? 0) - $casualties), 2);
            $aState['population'] = round(max(0, ($aState['population'] ?? 0) - $casualties * 0.2), 2);
        } else {
            // Target defends: aggressor loses more
            $casualties = min($aPop * 0.3, $aPop);
            $aState['population'] = round(max(0, ($aState['population'] ?? 0) - $casualties), 2);
            $tState['population'] = round(max(0, ($tState['population'] ?? 0) - $casualties * 0.1), 2);
        }

        $aState['war_pressure'] = round(min(1.0, ($aState['war_pressure'] ?? 0) + 0.3), 4);
        $tState['war_pressure'] = round(min(1.0, ($tState['war_pressure'] ?? 0) + 0.5), 4);

        $updatedZones[$aggressorIdx]['state'] = $aState;
        $updatedZones[$targetIdx]['state'] = $tState;

        $result->stateChanges['zones'] = array_values($updatedZones);

        $result->events[] = WorldEvent::create(
            type: WorldEventType::BATTLE_FOUGHT,
            universeId: $ctx->getUniverseId(),
            tick: $tick,
            payload: [
                'winner' => $aRoll > $tRoll ? 'aggressor' : 'defender',
                'casualties' => round($aRoll > $tRoll ? $tPop * 0.3 : $aPop * 0.3, 2),
            ],
            impactScore: 0.8
        );

        return $result;
    }
}
