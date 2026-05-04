<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;
use App\Modules\World\Models\UniverseBridge;
use App\Modules\Simulation\Core\Effects\WorldStateUpdateEffect;
use App\Modules\Simulation\Core\Events\WorldEvent;

/**
 * Phase 7/9: Multiverse Causal Bridge Engine 🌉⚛️
 * 
 * Manages causal links between universes. When a bridge is active, it may bleed
 * events or resonance from this universe into a target universe.
 * Phase 9 Update: Also pulls collapse entropy from target universes back to this universe.
 * 
 * Category: COSMETIC (Auto-skipped under high load)
 */
class CausalBridgeEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    public function name(): string
    {
        return 'causal_bridge';
    }

    public function priorityCategory(): string
    {
        return 'COSMETIC';
    }

    public function version(): string
    {
        return '2.1.0';
    }

    public function priority(): int
    {
        return 900; // Run late in the pipeline
    }

    public function tickRate(): int
    {
        return 5;
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $universeId = $ctx->getUniverseId();
        $tick = $ctx->getTick();

        // 1. Find active bridges departing from this universe
        $bridges = UniverseBridge::with('targetUniverse')
            ->where('source_universe_id', $universeId)
            ->where('is_active', true)
            ->get();

        if ($bridges->isEmpty()) {
            return EngineResult::empty();
        }

        $events = [];
        $effects = [];
        $totalBleedEntropy = 0;

        foreach ($bridges as $bridge) {
            // Chance of resonance event based on resonance_level
            if ((mt_rand(0, 1000) / 1000) < $bridge->resonance_level) {
                // Emit event targetting the target universe
                $events[] = WorldEvent::create(
                    type: 'MULTIVERSE_RESONANCE',
                    universeId: (int) $bridge->target_universe_id,
                    tick: $tick,
                    payload: [
                        'source_universe_id' => $universeId,
                        'bridge_type' => $bridge->bridge_type,
                        'resonance_score' => $bridge->resonance_level,
                    ],
                    impactScore: $bridge->resonance_level * 10
                );

                Log::info("CausalBridgeEngine: Resonance bleed from #{$universeId} to #{$bridge->target_universe_id}");
            }

            // Phase 9: Collapse propagation (pull entropy from a dying target back to the source)
            $target = $bridge->targetUniverse;
            if ($target) {
                $targetEntropy = (float)($target->entropy ?? 0);
                $targetStability = (float)($target->stability_index ?? 1);
                
                if ($targetEntropy > 0.95 && $targetStability < 0.15) {
                    $bleedMultiplier = \config('worldos.multiverse.collapse_bleed_rate', 0.1);
                    $bleedEntropy = $bridge->resonance_level * $bleedMultiplier;
                    $totalBleedEntropy += $bleedEntropy;
                    
                    $events[] = WorldEvent::create(
                        type: 'COLLAPSE_BLEED',
                        universeId: $universeId,
                        tick: $tick,
                        payload: [
                            'target_universe_id' => $target->id,
                            'bleed_entropy' => $bleedEntropy,
                            'bridge_type' => $bridge->bridge_type,
                        ],
                        impactScore: $bleedEntropy * 100
                    );
                    
                    Log::warning("CausalBridgeEngine: Collapse bleed pulled from dying target #{$target->id} into #{$universeId} (Bleed: {$bleedEntropy})");
                }
            }
        }

        $updates = ['meta.last_resonance_tick' => $tick];
        
        if ($totalBleedEntropy > 0) {
            // Calculate new entropy for current universe directly to ensure clamping [0,1]
            $currentEntropy = $state->getEntropy();
            $newEntropy = min(1.0, $currentEntropy + $totalBleedEntropy);
            $updates['entropy'] = $newEntropy;
            $updates['stability_index'] = max(0.0, $state->getStabilityIndex() - ($totalBleedEntropy * 0.5));
        }

        if (!empty($events) || $totalBleedEntropy > 0) {
            $effects[] = new WorldStateUpdateEffect($updates);
        }

        return new EngineResult($events, $effects);
    }
}

