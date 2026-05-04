<?php

namespace App\Modules\Simulation\Core\Engines\Biological;

use App\Contracts\Repositories\UniverseRepositoryInterface;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Core\SimulationEventBus;
use Illuminate\Support\Facades\Log;

use App\Modules\Simulation\Core\Events\EcologicalPhaseTransitionEvent;

/**
 * Ecological Phase Transition Engine (Tier 2).
 * EnvironmentState (temperature, rainfall) and EcosystemState (forest, grassland, desert).
 * When a threshold is crossed, transition gradually (progress 0→1); affects resource_regeneration per zone.
 * Writes PhaseTransitionEvent (Chronicle) on completion.
 */
class EcologicalPhaseTransitionEngine
{
    private const BIOMES = ['forest', 'grassland', 'desert'];

    public function __construct(
        protected SimulationEventBus $eventBus
    ) {}

    /**
     * Evaluate zones; advance or start transitions.
     */
    public function runWithState(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, int $currentTick): void
    {
        $interval = (int) config('worldos.intelligence.ecological_phase_transition_tick_interval', 100);
        if ($interval <= 0 || $currentTick % $interval !== 0) {
            return;
        }

        $zones = $state->get('zones');
        if (!is_array($zones) || empty($zones)) {
            return;
        }

        $durationTicks = max(1, (int) config('worldos.intelligence.ecological_phase_transition_duration_ticks', 50));
        $rainfallDesertMax = (float) config('worldos.intelligence.ecological_phase_transition_rainfall_desert_max', 0.35);
        $rainfallForestMin = (float) config('worldos.intelligence.ecological_phase_transition_rainfall_forest_min', 0.65);
        
        $universeId = (int) $state->get('universe_id', 0);
        $seed = $universeId * 31 + (int) ($state->get('seed', 0));

        $zonesModified = false;
        foreach ($zones as $zoneIndex => &$zone) {
            $zoneState = &$zone['state'];
            if (!is_array($zoneState)) {
                $zoneState = [];
            }

            $rainfall = (float) ($zoneState['rainfall'] ?? 0.5);
            $temperature = (float) ($zoneState['temperature'] ?? 0.5);

            $currentBiome = $this->normalizeBiome($zoneState['ecosystem_state'] ?? 'grassland');
            $targetBiomeFromEnv = $this->rainfallToBiome($rainfall, $rainfallDesertMax, $rainfallForestMin);

            $targetBiome = $zoneState['target_ecosystem_state'] ?? null;
            $targetBiome = $targetBiome ? $this->normalizeBiome($targetBiome) : null;
            $progress = (float) ($zoneState['transition_progress'] ?? 0);

            if ($targetBiome !== null && $progress >= 1.0) {
                $fromState = $currentBiome;
                $zoneState['ecosystem_state'] = $targetBiome;
                unset($zoneState['target_ecosystem_state'], $zoneState['transition_progress']);
                $zonesModified = true;
                $this->chroniclePhaseTransition($state, $currentTick, $fromState, $targetBiome, $zoneIndex);
                continue;
            }

            if ($targetBiome !== null) {
                $progress += 1.0 / $durationTicks;
                $zoneState['transition_progress'] = min(1.0, $progress);
                $zonesModified = true;
                if ($zoneState['transition_progress'] >= 1.0) {
                    $fromState = $currentBiome;
                    $zoneState['ecosystem_state'] = $targetBiome;
                    unset($zoneState['target_ecosystem_state'], $zoneState['transition_progress']);
                    $this->chroniclePhaseTransition($state, $currentTick, $fromState, $targetBiome, $zoneIndex);
                }
                continue;
            }

            if ($targetBiomeFromEnv !== $currentBiome) {
                $zoneState['target_ecosystem_state'] = $targetBiomeFromEnv;
                $zoneState['transition_progress'] = 0.0;
                $zonesModified = true;
            } else {
                if (!isset($zoneState['ecosystem_state'])) {
                    $zoneState['ecosystem_state'] = $currentBiome;
                    $zonesModified = true;
                }
            }
        }
        unset($zone, $zoneState);

        if ($zonesModified) {
            $state->set('zones', $zones);
        }
    }

    public function evaluate(Universe $universe, int $currentTick): void
    {
        // Deprecated
    }

    private function rainfallToBiome(float $rainfall, float $desertMax, float $forestMin): string
    {
        if ($rainfall <= $desertMax) {
            return 'desert';
        }
        if ($rainfall >= $forestMin) {
            return 'forest';
        }
        return 'grassland';
    }

    private function normalizeBiome(string $b): string
    {
        $b = strtolower(trim($b));
        return in_array($b, self::BIOMES, true) ? $b : 'grassland';
    }

    private function chroniclePhaseTransition(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, int $tick, string $fromState, string $toState, int $zoneIndex): void
    {
        $universeId = (int)$state->get('universe_id');
        $content = sprintf(
            'Ecological phase transition at tick %d: zone %d %s → %s.',
            $tick,
            $zoneIndex,
            $fromState,
            $toState
        );
        
        // V10 Chronicling & Eventing
        Log::info("EcologicalPhaseTransitionEngine: Universe {$universeId} zone {$zoneIndex} {$fromState} → {$toState} at tick {$tick}");
        
        $this->eventBus->dispatch(new EcologicalPhaseTransitionEvent($universeId, $tick, [
            'from_state' => $fromState,
            'to_state' => $toState,
            'zone_index' => $zoneIndex,
        ]));
    }

    /**
     * Get resource regen factor for a zone (for ProcessActorEnergyAction).
     * During transition, blend from and to biome factors by progress.
     */
    public static function resourceRegenFactorForZone(array $zoneState): float
    {
        $factors = config('worldos.intelligence.ecological_phase_transition_biome_resource_regen', [
            'forest' => 1.2,
            'grassland' => 1.0,
            'desert' => 0.6,
        ]);
        $from = $zoneState['ecosystem_state'] ?? 'grassland';
        $to = $zoneState['target_ecosystem_state'] ?? $from;
        $progress = (float) ($zoneState['transition_progress'] ?? 0);
        $fromFactor = (float) ($factors[$from] ?? $factors['grassland'] ?? 1.0);
        $toFactor = (float) ($factors[$to] ?? $factors['grassland'] ?? 1.0);
        return $fromFactor * (1.0 - $progress) + $toFactor * $progress;
    }

    public function handle(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, \App\Modules\Simulation\Core\Domain\TickContext $ctx): \App\Modules\Simulation\Core\Engines\EngineResult
    {
        $this->runWithState($state, $ctx->getTick());
        return \App\Modules\Simulation\Core\Engines\EngineResult::empty();
    }
}


