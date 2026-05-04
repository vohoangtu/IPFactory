<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Effects\WorldStateUpdateEffect;

/**
 * MemoryReflectionEngine – Translates physical events into collective memory/trauma.
 * 
 * Logic: High-impact negative events (war, famine) increase trauma_index.
 * Positive events (wealth, innovation) create "glories".
 */
class MemoryReflectionEngine implements SimulationEngine
{
    public function name(): string { return 'MemoryReflectionEngine'; }
    public function version(): string { return '1.0.0'; }
    public function phase(): string { return 'META'; }
    public function priority(): int { return 400; }
    public function priorityCategory(): string { return 'STOCHASTIC'; }
    public function tickRate(): int { return 5; }
    public function isParallelSafe(): bool { return true; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $effects = [];
        $zones = $state->getZones();
        
        foreach ($zones as $zoneId => $zone) {
            $memory = $state->get("memory.zones.{$zoneId}", [
                'trauma_index' => 0.0,
                'past_events' => []
            ]);

            // Simple heuristic for trauma: high fear and low wealth in a zone
            $fields = $zone['fields'] ?? [];
            $fear = (float)($fields['fear'] ?? 0.5);
            $wealth = (float)($fields['wealth'] ?? 0.5);
            
            if ($fear > 0.7 && $wealth < 0.3) {
                $memory['trauma_index'] = min(1.0, $memory['trauma_index'] + 0.05);
                $memory['past_events'][] = [
                    'tick' => $ctx->getTick(),
                    'type' => 'Hardship',
                    'significance' => 0.6
                ];
            }

            // Decay trauma over time
            $memory['trauma_index'] = max(0.0, $memory['trauma_index'] * 0.98);

            // Cap memory size
            if (count($memory['past_events']) > 10) {
                array_shift($memory['past_events']);
            }

            $effects[] = new WorldStateUpdateEffect([
                "memory.zones.{$zoneId}" => $memory
            ]);
        }

        return new EngineResult([], $effects);
    }
}
