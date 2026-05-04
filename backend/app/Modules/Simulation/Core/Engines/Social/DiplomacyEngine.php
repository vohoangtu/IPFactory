<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Core\Engines\Social;

use App\Modules\World\Models\DiplomaticTreaty;
use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Events\WorldEventType;
use App\Modules\Simulation\Core\Runtime\State\WorldState;

/**
 * DiplomacyEngine — Treaty management and faction tension calculation.
 *
 * 1. Expire treaties where ends_at_tick <= current tick, emit TREATY_EXPIRED events
 * 2. Calculate ideology distance (Euclidean) between all faction pairs
 * 3. Apply alliance modifier (0.5x) to reduce tension for allied factions
 * Output: diplomacy.tensions = { '{id_i}_{id_j}': { ideology_distance, has_alliance, base_tension } }
 */
class DiplomacyEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string
    {
        return 'DiplomacyEngine';
    }

    public function phase(): string
    {
        return 'politics';
    }

    public function priority(): int
    {
        return 20;
    }

    public function tickRate(): int
    {
        return 1;
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $universeId = $ctx->getUniverseId();
        $currentTick = $ctx->getTick();

        $result = new EngineResult();

        // 1. Expire treaties
        $expiredTreaties = DiplomaticTreaty::where('universe_id', $universeId)
            ->where('is_active', true)
            ->where('ends_at_tick', '<=', $currentTick)
            ->get();

        foreach ($expiredTreaties as $treaty) {
            $treaty->update(['is_active' => false]);

            $result->events[] = [
                'type' => WorldEventType::TREATY_EXPIRED,
                'source_civ_id' => $treaty->source_civ_id,
                'target_civ_id' => $treaty->target_civ_id,
                'treaty_type' => $treaty->treaty_type,
                'tick' => $currentTick,
            ];
        }

        // 2. Get active alliances for tension calculation
        $activeTreaties = DiplomaticTreaty::where('universe_id', $universeId)
            ->where('is_active', true)
            ->where('treaty_type', 'ALLIANCE')
            ->get();

        // Build alliance lookup: key = '{min_id}_{max_id}'
        $alliances = [];
        foreach ($activeTreaties as $treaty) {
            $key = min($treaty->source_civ_id, $treaty->target_civ_id)
                . '_' . max($treaty->source_civ_id, $treaty->target_civ_id);
            $alliances[$key] = true;
        }

        // 3. Calculate tensions between all faction pairs
        $factions = $state->get('factions', []);
        $tensions = [];

        $factionCount = count($factions);
        for ($i = 0; $i < $factionCount; $i++) {
            for ($j = $i + 1; $j < $factionCount; $j++) {
                $factionA = $factions[$i];
                $factionB = $factions[$j];

                $idA = (int) ($factionA['id'] ?? $i);
                $idB = (int) ($factionB['id'] ?? $j);

                $vectorA = $factionA['ideology_vector'] ?? [];
                $vectorB = $factionB['ideology_vector'] ?? [];

                $ideologyDistance = $this->euclideanDistance($vectorA, $vectorB);

                $allianceKey = min($idA, $idB) . '_' . max($idA, $idB);
                $hasAlliance = isset($alliances[$allianceKey]);

                $baseTension = $ideologyDistance * ($hasAlliance ? 0.5 : 1.0);

                $tensionKey = $idA . '_' . $idB;
                $tensions[$tensionKey] = [
                    'ideology_distance' => round($ideologyDistance, 4),
                    'has_alliance' => $hasAlliance,
                    'base_tension' => round($baseTension, 4),
                ];
            }
        }

        if (! empty($tensions)) {
            $result->stateChanges[] = [
                'diplomacy.tensions' => $tensions,
            ];
        }

        return $result;
    }

    /**
     * Euclidean distance between two ideology vectors.
     */
    private function euclideanDistance(array $a, array $b): float
    {
        $sumSquares = 0.0;
        $length = max(count($a), count($b));

        for ($i = 0; $i < $length; $i++) {
            $va = (float) ($a[$i] ?? 0.0);
            $vb = (float) ($b[$i] ?? 0.0);
            $sumSquares += ($va - $vb) ** 2;
        }

        return sqrt($sumSquares);
    }
}
