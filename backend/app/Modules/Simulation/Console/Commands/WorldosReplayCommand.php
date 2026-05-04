<?php

namespace App\Modules\Simulation\Console\Commands;

use App\Contracts\SimulationEngineClientInterface;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Repositories\UniverseSnapshotRepository;
use App\Modules\Simulation\Core\EngineRegistry;
use Illuminate\Console\Command;

/**
 * Doc 21 §4d: Replay simulation from tick N to M for deterministic debugging.
 * Loads snapshot at from-tick, sends state + seed to Rust engine, runs (M - N) ticks, optionally compares with stored snapshot at to-tick.
 */
class WorldosReplayCommand extends Command
{
    protected $signature = 'worldos:replay
                            {universe : Universe ID}
                            {--from-tick= : Start tick (snapshot must exist)}
                            {--to-tick= : End tick (optional; if present and snapshot exists, compare output)}
                            {--allow-manifest-mismatch : Run even when snapshot engine_manifest differs from current (replay may be non-deterministic)}';

    protected $description = 'Doc 21 §4d: Replay simulation from from-tick to to-tick for deterministic debugging';

    public function handle(
        SimulationEngineClientInterface $engine,
        UniverseSnapshotRepository $snapshots,
        EngineRegistry $engineRegistry
    ): int {
        $universeId = (int) $this->argument('universe');
        $fromTick = $this->option('from-tick') !== null ? (int) $this->option('from-tick') : null;
        $toTick = $this->option('to-tick') !== null ? (int) $this->option('to-tick') : null;

        if ($fromTick === null) {
            $this->error('--from-tick is required.');
            return 1;
        }

        $universe = Universe::find($universeId);
        if (! $universe || ! $universe->world) {
            $this->error("Universe {$universeId} not found or has no world.");
            return 1;
        }

        $snapAtN = $snapshots->getAtTick($universeId, $fromTick);
        if (! $snapAtN) {
            $this->error("No snapshot at tick {$fromTick} for universe {$universeId}.");
            return 1;
        }

        $storedManifest = is_array($snapAtN->metrics['engine_manifest'] ?? null) ? $snapAtN->metrics['engine_manifest'] : null;
        $currentManifest = $engineRegistry->getManifest();
        if ($storedManifest !== null && $storedManifest !== $currentManifest) {
            if (! $this->option('allow-manifest-mismatch')) {
                $this->error('Engine manifest at snapshot differs from current. Deterministic replay requires same seed + same engine_manifest. Use --allow-manifest-mismatch to run anyway (non-deterministic).');
                return 1;
            }
            $this->warn('Engine manifest at snapshot differs from current (replay may not be deterministic).');
        }

        $ticksToRun = $toTick !== null ? $toTick - $fromTick : 1;
        if ($ticksToRun < 1) {
            $this->error('to-tick must be greater than from-tick.');
            return 1;
        }

        $stateVector = is_array($snapAtN->state_vector) ? $snapAtN->state_vector : [];
        $zones = $stateVector['zones'] ?? [];
        foreach ($zones as &$zone) {
            if (! isset($zone['state']['structured_mass'])) {
                $zone['state']['structured_mass'] = 50.0;
            }
        }
        unset($zone);

        $stateInput = [
            'universe_id' => $universeId,
            'tick' => $fromTick,
            'zones' => $zones,
            'global_entropy' => (float) ($stateVector['global_entropy'] ?? ($stateVector['entropy'] ?? 0.5)),
            'knowledge_core' => (float) ($stateVector['knowledge_core'] ?? 0),
            'scars' => $stateVector['scars'] ?? [],
            'attractors' => $stateVector['attractors'] ?? [],
            'dark_attractors' => $stateVector['dark_attractors'] ?? [],
            'institutions' => [],
        ];

        $worldConfig = [
            'world_id' => $universe->world->id,
            'origin' => $universe->world->origin ?? 'generic',
            'axiom' => $universe->world->axiom,
            'world_seed' => $universe->world->world_seed,
            'genome' => $universe->kernel_genome ?? null,
        ];

        $this->info("Replaying universe {$universeId} from tick {$fromTick} for {$ticksToRun} tick(s).");
        $response = $engine->advance($universeId, $ticksToRun, $stateInput, $worldConfig);

        if (! ($response['ok'] ?? false)) {
            $this->error($response['error_message'] ?? 'Engine advance failed.');
            return 1;
        }

        $resultTick = (int) ($response['snapshot']['tick'] ?? $fromTick + $ticksToRun);
        $this->info("Engine returned snapshot at tick {$resultTick}.");

        if ($toTick !== null && $resultTick === $toTick) {
            $storedAtM = $snapshots->getAtTick($universeId, $toTick);
            if ($storedAtM) {
                $storedVec = is_array($storedAtM->state_vector) ? $storedAtM->state_vector : (is_string($storedAtM->state_vector) ? (json_decode($storedAtM->state_vector, true) ?? []) : []);
                $rawComputed = $response['snapshot']['state_vector'] ?? [];
                $computedVec = is_array($rawComputed) ? $rawComputed : (is_string($rawComputed) ? (json_decode($rawComputed, true) ?? []) : []);
                $storedHash = hash('sha256', json_encode($storedVec));
                $computedHash = hash('sha256', json_encode($computedVec));
                if ($storedHash === $computedHash) {
                    $this->info('Determinism check: match (stored snapshot at to-tick equals replayed output).');
                } else {
                    $this->warn('Determinism check: mismatch (stored snapshot at to-tick differs from replayed output).');
                    $this->line('Stored hash: ' . $storedHash);
                    $this->line('Replayed hash: ' . $computedHash);
                }
            }
        }

        return 0;
    }
}


