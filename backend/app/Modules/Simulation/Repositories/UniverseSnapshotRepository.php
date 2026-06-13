<?php

namespace App\Modules\Simulation\Repositories;
use App\Contracts\Repositories\UniverseSnapshotRepositoryInterface;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Services\Core\ObserverService;

class UniverseSnapshotRepository implements UniverseSnapshotRepositoryInterface
{
    public function __construct(
        protected ?ObserverService $observer = null,
        protected \App\Modules\Intelligence\Services\AI\EpistemicService $epistemic
    ) {}

    /**
     * Save snapshot for universe (from engine advance response).
     */
    public function save(Universe $universe, array $snapshot): UniverseSnapshot
    {
        try {
            $model = UniverseSnapshot::updateOrCreate(
                [
                    'universe_id' => $universe->id,
                    'tick' => $snapshot['tick'],
                ],
                [
                    'state_vector' => $snapshot['state_vector'] ?? [],
                    'entropy' => $snapshot['entropy'] ?? null,
                    'stability_index' => $snapshot['stability_index'] ?? null,
                    'metrics' => $snapshot['metrics'] ?? null,
                ]
            );

            $universe->update([
                'current_tick' => $snapshot['tick'],
                'state_vector' => $snapshot['state_vector'] ?? [],
            ]);

            if ($this->observer) {
                $this->observer->publishSnapshot(
                    $universe->id,
                    $universe->multiverse_id,
                    $snapshot['tick'],
                    ['entropy' => $snapshot['entropy'] ?? null, 'stability_index' => $snapshot['stability_index'] ?? null]
                );
            }

            // Broadcast realtime event for Centrifugo
            event(new \App\Modules\Simulation\Events\UniversePulsed($universe, $model));

            return $model;
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle race conditions where two pulses try to save same tick
            if ($e->getCode() == '23505') { // Postgres UNIQUE_VIOLATION
                \Illuminate\Support\Facades\Log::warning("UniverseSnapshot UNIQUE_VIOLATION for Universe {$universe->id} T{$snapshot['tick']}. Re-fetching existing model.");
                return UniverseSnapshot::where('universe_id', $universe->id)->where('tick', $snapshot['tick'])->firstOrFail();
            }
            throw $e;
        }
    }

    /**
     * Get snapshot at specific tick.
     */
    public function getAtTick(int $universeId, int $tick): ?UniverseSnapshot
    {
        $snap = UniverseSnapshot::where('universe_id', $universeId)
            ->where('tick', $tick)
            ->first();
            
        return $snap ? $this->enrich($snap) : null;
    }

    /**
     * Get latest snapshot for universe.
     */
    public function getLatest(int $universeId): ?UniverseSnapshot
    {
        $snap = UniverseSnapshot::where('universe_id', $universeId)
            ->orderByDesc('tick')
            ->first();
            
        return $snap ? $this->enrich($snap) : null;
    }

    protected function enrich(UniverseSnapshot $snapshot): UniverseSnapshot
    {
        $instability = ($snapshot->metrics ?? [])['instability_gradient'] ?? (($snapshot->state_vector ?? [])['epistemic_instability'] ?? 0);
        $snapshot->existence_state = $this->epistemic->getExistenceState((float)$instability);
        
        // Dynamic stability check
        $universe = Universe::find($snapshot->universe_id);
        if ($universe) {
            $snapshot->reality_stability = $this->epistemic->calculateStability($universe);
        }

        return $snapshot;
    }
}


