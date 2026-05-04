<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Narrative\Models\Narrative;

/**
 * NarrativeConflictEngine – Handles competition between different narratives.
 * 
 * Logic: If two narratives have conflicting tags (e.g. "hope" vs "fear"),
 * the one with higher virality suppresses the other.
 */
class NarrativeConflictEngine implements SimulationEngine
{
    public function name(): string { return 'NarrativeConflictEngine'; }
    public function version(): string { return '1.0.0'; }
    public function phase(): string { return 'META'; }
    public function priority(): int { return 300; }
    public function priorityCategory(): string { return 'STOCHASTIC'; }
    public function tickRate(): int { return 2; } // Runs every 2 ticks
    public function isParallelSafe(): bool { return true; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $universeId = $ctx->getUniverseId();
        $narratives = Narrative::where('universe_id', $universeId)
            ->where('is_active', true)
            ->get();

        if ($narratives->count() < 2) {
            return new EngineResult();
        }

        // Simple conflict logic: polarization
        // If we have a 'rationalism' tag and a 'superstition' tag, they conflict.
        foreach ($narratives as $nA) {
            foreach ($narratives as $nB) {
                if ($nA->id === $nB->id) continue;

                if ($this->isConflicting($nA, $nB)) {
                    // Stronger one eats the weaker one's virality
                    if ($nA->virality > $nB->virality) {
                        $nB->update(['virality' => $nB->virality * 0.8]);
                        $nA->update(['virality' => $nA->virality * 1.05]);
                    }
                }
            }
        }

        return new EngineResult();
    }

    protected function isConflicting(Narrative $a, Narrative $b): bool
    {
        $conflicts = [
            ['rationalism', 'superstition'],
            ['hope', 'fear'],
            ['order', 'chaos'],
            ['isolation', 'expansion']
        ];

        foreach ($conflicts as $pair) {
            if (in_array($pair[0], $a->tags) && in_array($pair[1], $b->tags)) return true;
            if (in_array($pair[1], $a->tags) && in_array($pair[0], $b->tags)) return true;
        }

        return false;
    }
}

