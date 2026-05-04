<?php

namespace App\Modules\Simulation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Simulation\Actions\QueryObserverDashboardAction;
use App\Modules\Simulation\Actions\ExecuteObserverCommandAction;
use Illuminate\Http\JsonResponse;

/**
 * Phase 77: Apex Observer API — Demiurge Vision 👁️✨
 *
 * "Con mắt của Đấng Tạo Hóa nhìn thấu ngàn vũ trụ cùng lúc."
 * Provides privileged read/write access to the simulation's deepest state layers.
 *
 * Delegates to:
 * - QueryObserverDashboardAction (state-based dashboard projections)
 * - ExecuteObserverCommandAction (mutation chronicle, time-travel, delta)
 */
class ApexObserverController extends Controller
{
    public function __construct(
        protected QueryObserverDashboardAction $dashboardAction,
        protected ExecuteObserverCommandAction $commandAction,
    ) {}

    /**
     * GET /api/apex/wavefunction/{universeId}
     * Project the full causal wavefunction of a universe.
     */
    public function projectWavefunction(int $universeId): JsonResponse
    {
        $result = $this->dashboardAction->projectWavefunction($universeId);

        if ($result === null) {
            return response()->json([
                'error' => 'Universe state could not be loaded',
                'universe_id' => $universeId,
            ], 404);
        }

        return response()->json($result);
    }

    /**
     * GET /api/apex/informational-mass/{universeId}
     * Return the informational mass — the "weight" of meaning in the universe.
     */
    public function getInformationalMass(int $universeId): JsonResponse
    {
        $result = $this->dashboardAction->getInformationalMass($universeId);

        if ($result === null) {
            return response()->json(['error' => 'State not loaded'], 404);
        }

        return response()->json($result);
    }

    /**
     * GET /api/apex/mutation-chronicle/{universeId}
     * List all autopoietic mutations applied to the active DSL layers.
     */
    public function getMutationChronicle(int $universeId): JsonResponse
    {
        $result = $this->commandAction->getMutationChronicle($universeId);

        return response()->json($result);
    }

    /**
     * GET /api/apex/mutation-chronicle/{universeId}/{dslHash}
     * Return the latest mutation detail with before/after contents.
     */
    public function getMutationDetail(int $universeId, string $dslHash): JsonResponse
    {
        $result = $this->commandAction->getMutationDetail($universeId, $dslHash);

        if ($result === null) {
            return response()->json([
                'error' => 'Mutation not found',
                'dsl_hash' => $dslHash,
            ], 404);
        }

        return response()->json($result);
    }

    /**
     * GET /api/apex/meaning-seeds
     * List all extracted meaning seeds from collapsed universes.
     */
    public function getMeaningSeeds(): JsonResponse
    {
        $result = $this->commandAction->getMeaningSeeds();

        return response()->json($result);
    }

    /**
     * GET /api/apex/v10/universes/{universeId}/state-at/{tick}
     * Vector 4: Time-Travel — wavefunction at a specific historical tick.
     */
    public function stateAtTick(int $universeId, int $tick): JsonResponse
    {
        $result = $this->commandAction->stateAtTick($universeId, $tick);

        if ($result === null) {
            return response()->json(['error' => 'No snapshot at or before tick ' . $tick], 404);
        }

        return response()->json($result);
    }

    /**
     * GET /api/apex/v10/universes/{universeId}/delta?from={tick}&to={tick}
     * Vector 4: Delta comparison between two historic ticks.
     */
    public function compareDelta(int $universeId): JsonResponse
    {
        $fromTick = (int) request()->query('from', 0);
        $toTick   = (int) request()->query('to', PHP_INT_MAX);

        $result = $this->commandAction->compareDelta($universeId, $fromTick, $toTick);

        if ($result === null) {
            return response()->json(['error' => 'Snapshots not found for both ticks'], 404);
        }

        return response()->json($result);
    }

    /**
     * GET /api/apex/v10/universes/{universeId}/topology
     * V8 Representative: Causal Topology Graph Data.
     */
    public function getTopology(int $universeId): JsonResponse
    {
        $result = $this->dashboardAction->getTopology($universeId);

        if ($result === null) {
            return response()->json(['error' => 'State not loaded'], 404);
        }

        return response()->json($result);
    }

    /**
     * GET /api/apex/v10/universes/{universeId}/consciousness
     * V10 Representative: Consciousness Heatmap Data.
     */
    public function getConsciousnessField(int $universeId): JsonResponse
    {
        $result = $this->dashboardAction->getConsciousnessField($universeId);

        if ($result === null) {
            return response()->json(['error' => 'State not loaded'], 404);
        }

        return response()->json($result);
    }

    /**
     * GET /api/apex/v10/universes/{universeId}/ascension-filters
     * V9 Representative: Great Filter Radar Data.
     */
    public function getAscensionStatus(int $universeId): JsonResponse
    {
        $result = $this->dashboardAction->getAscensionStatus($universeId);

        if ($result === null) {
            return response()->json(['error' => 'State not loaded'], 404);
        }

        return response()->json($result);
    }
}
