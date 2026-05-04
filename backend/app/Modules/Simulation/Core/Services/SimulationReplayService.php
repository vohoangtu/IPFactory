<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Core\Services;

use App\Modules\Simulation\Models\TickManifest;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\WorldKernel;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

/**
 * Phase 8: Deterministic Replay Service
 * Replays a simulation tick using a saved TickManifest to verify consistency.
 */
final class SimulationReplayService
{
    public function __construct(
        private readonly WorldKernel $kernel,
    ) {}

    /**
     * Replay a specific tick for a universe using its saved manifest.
     * Returns a ReplayResult containing the re-run results and any detected divergences.
     *
     * @return array{ok: bool, divergences: array, replay_events: array, replay_effects: int, manifest_events: int, manifest_effects: int}
     */
    public function replay(int $universeId, int $tick): array
    {
        // 1. Load the manifest for this tick
        $manifest = TickManifest::where('universe_id', $universeId)
            ->where('tick', $tick)
            ->latest()
            ->first();

        if (!$manifest) {
            return [
                'ok' => false,
                'error' => "No manifest found for universe {$universeId} at tick {$tick}.",
                'divergences' => [],
            ];
        }

        // 2. Load the snapshot BEFORE this tick (tick - 1)
        $snapshot = UniverseSnapshot::where('universe_id', $universeId)
            ->where('tick', $tick - 1)
            ->latest()
            ->first();

        if (!$snapshot) {
            // If no tick-1 snapshot, try to use tick-0 or any existing one
            $snapshot = UniverseSnapshot::where('universe_id', $universeId)
                ->where('tick', '<', $tick)
                ->orderByDesc('tick')
                ->first();

            if (!$snapshot) {
                return [
                    'ok' => false,
                    'error' => "No snapshot found before tick {$tick} for universe {$universeId}.",
                    'divergences' => [],
                ];
            }
        }

        // 3. Reconstruct WorldState from the snapshot
        $stateVector = $snapshot->state_vector;
        if (is_string($stateVector)) {
            $stateVector = json_decode($stateVector, true) ?? [];
        }
        $state = new WorldState((int)$snapshot->tick, $stateVector ?: []);

        // 4. Reconstruct TickContext with the SAVED seed
        $ctx = new TickContext(
            universeId: $universeId,
            tick: $tick,
            seed: (int)$manifest->seed,
            metadata: ['is_replay' => true],
        );

        // 5. Re-run the tick
        try {
            $result = $this->kernel->runTick($state, $ctx);
        } catch (\Throwable $e) {
            Log::error('SimulationReplayService: Replay failed during kernel execution', [
                'universe_id' => $universeId,
                'tick'        => $tick,
                'error'       => $e->getMessage(),
            ]);
            return [
                'ok' => false,
                'error' => 'Replay execution failed: ' . $e->getMessage(),
                'divergences' => [],
            ];
        }

        // 6. Compare re-run events vs recorded manifest events
        $replayEventTypes = array_map(fn($ev) => $ev->type ?? ($ev['type'] ?? 'unknown'), $result->events);
        $manifestEventTypes = array_map(fn($ev) => $ev['type'] ?? 'unknown', $manifest->events ?? []);

        sort($replayEventTypes);
        sort($manifestEventTypes);

        $divergences = [];

        if ($replayEventTypes !== $manifestEventTypes) {
            $divergences[] = [
                'field' => 'events',
                'original' => $manifestEventTypes,
                'replay' => $replayEventTypes,
                'note' => 'Event types differ between original run and replay.',
            ];
        }

        $originalEffectCount = count($manifest->effects ?? []);
        $replayEffectCount = count($result->engineMetrics);

        return [
            'ok' => true,
            'is_deterministic' => empty($divergences),
            'divergences' => $divergences,
            'replay_events' => $replayEventTypes,
            'manifest_events' => $manifestEventTypes,
            'replay_effect_count' => $replayEffectCount,
            'manifest_effect_count' => $originalEffectCount,
            'original_tick' => $tick,
            'original_seed' => $manifest->seed,
        ];
    }
}

