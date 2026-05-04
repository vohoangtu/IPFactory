<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Actions;

use App\Modules\Simulation\Services\Core\RuleMutationService;
use App\Modules\Simulation\Models\UniverseSnapshot;
use Illuminate\Support\Facades\Storage;

/**
 * Phase 77: Apex Observer — Command Processing
 *
 * Extracts observer query/command logic from ApexObserverController.
 * Handles: mutation chronicle, mutation detail, meaning seeds, state-at-tick, delta comparison.
 */
class ExecuteObserverCommandAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected RuleMutationService $mutationService,
    ) {}

    /**
     * List all autopoietic mutations applied to the active DSL layers.
     */
    public function getMutationChronicle(int $universeId): array
    {
        $chronicle = array_values(array_filter(
            $this->mutationService->getMutationChronicle(),
            fn(array $entry) => !isset($entry['universe_id']) || $entry['universe_id'] === null || (int) $entry['universe_id'] === $universeId
        ));

        return [
            'universe_id' => $universeId,
            'total_mutations' => count($chronicle),
            'chronicle' => $chronicle,
        ];
    }

    /**
     * Return the latest mutation detail with before/after contents.
     * Returns null if mutation not found or doesn't belong to universe.
     */
    public function getMutationDetail(int $universeId, string $dslHash): ?array
    {
        $detail = $this->mutationService->getMutationDetail($dslHash);

        if ($detail === null) {
            return null;
        }

        $metadataUniverseId = isset($detail['metadata']['universe_id']) ? (int) $detail['metadata']['universe_id'] : null;
        if ($metadataUniverseId !== null && $metadataUniverseId !== $universeId) {
            return null;
        }

        return [
            'universe_id' => $universeId,
            'detail' => $detail,
        ];
    }

    /**
     * List all extracted meaning seeds from collapsed universes.
     */
    public function getMeaningSeeds(): array
    {
        $files = Storage::disk('local')->files('simulation/meaning_seeds');
        $seeds = [];

        foreach ($files as $file) {
            $data = json_decode(Storage::disk('local')->get($file), true);
            if ($data) {
                $seeds[] = [
                    'source_universe' => $data['source_universe'],
                    'collapsed_at_tick' => $data['collapsed_at_tick'],
                    'attractor' => $data['attractor'],
                    'dominant_beliefs' => $data['dominant_beliefs'],
                    'entropy_at_collapse' => $data['entropy_at_collapse'],
                    'created_at' => $data['created_at'],
                ];
            }
        }

        return [
            'total_seeds' => count($seeds),
            'seeds' => $seeds,
        ];
    }

    /**
     * Time-Travel — wavefunction at a specific historical tick.
     * Returns null if no snapshot found.
     */
    public function stateAtTick(int $universeId, int $tick): ?array
    {
        $snapshot = UniverseSnapshot::where('universe_id', $universeId)
            ->where('tick', '<=', $tick)
            ->orderByDesc('tick')
            ->first();

        if (!$snapshot) {
            return null;
        }

        $sv = is_string($snapshot->state_vector) ? json_decode($snapshot->state_vector, true) : ($snapshot->state_vector ?? []);
        $entropy   = (float) ($sv['entropy']        ?? $snapshot->entropy        ?? 0);
        $stability = (float) ($sv['stability_index'] ?? $snapshot->stability_index ?? 0);

        return [
            'universe_id'    => $universeId,
            'requested_tick' => $tick,
            'actual_tick'    => $snapshot->tick,
            'snapshot_id'    => $snapshot->id,
            'wavefunction'   => [
                'entropy'              => $entropy,
                'stability_index'      => $stability,
                'information_density'  => (float) ($sv['meta']['information_density'] ?? 0.0),
                'active_attractor'     => $sv['active_attractor'] ?? 'unknown',
                'collapse_probability' => round(max(0, min(1, $entropy * (1 - $stability))), 4),
            ],
            'metrics' => $snapshot->metrics ?? [],
        ];
    }

    /**
     * Delta comparison between two historic ticks.
     * Returns null if snapshots not found for both ticks.
     */
    public function compareDelta(int $universeId, int $fromTick, int $toTick): ?array
    {
        $snapA = UniverseSnapshot::where('universe_id', $universeId)
            ->where('tick', '<=', $fromTick)->orderByDesc('tick')->first();
        $snapB = UniverseSnapshot::where('universe_id', $universeId)
            ->where('tick', '<=', $toTick)->orderByDesc('tick')->first();

        if (!$snapA || !$snapB) {
            return null;
        }

        $mA = $snapA->metrics ?? [];
        $mB = $snapB->metrics ?? [];
        $metricDeltas = [];
        foreach (array_unique(array_merge(array_keys($mA), array_keys($mB))) as $k) {
            if (is_numeric($mA[$k] ?? null) && is_numeric($mB[$k] ?? null)) {
                $metricDeltas[$k] = round((float)$mB[$k] - (float)$mA[$k], 4);
            }
        }

        return [
            'universe_id'      => $universeId,
            'from_tick'        => $snapA->tick,
            'to_tick'          => $snapB->tick,
            'entropy_delta'    => round(($snapB->entropy ?? 0) - ($snapA->entropy ?? 0), 4),
            'stability_delta'  => round(($snapB->stability_index ?? 0) - ($snapA->stability_index ?? 0), 4),
            'tick_span'        => $snapB->tick - $snapA->tick,
            'metric_deltas'    => $metricDeltas,
        ];
    }
}
