<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Http\Controllers;

use App\Modules\Simulation\Models\TickManifest;
use App\Modules\Simulation\Core\Services\SimulationReplayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Phase 8: Audit trail and deterministic replay API.
 */
final class AuditController
{
    public function __construct(
        private readonly SimulationReplayService $replayService,
    ) {}

    /**
     * GET /api/worldos/universes/{id}/audit/{tick}
     * Returns the full manifest for a specific tick.
     */
    public function show(int $id, int $tick): JsonResponse
    {
        $manifest = TickManifest::where('universe_id', $id)
            ->where('tick', $tick)
            ->latest()
            ->first();

        if (!$manifest) {
            return response()->json([
                'error' => "No audit manifest found for universe #{$id} at tick {$tick}.",
            ], 404);
        }

        return response()->json([
            'universe_id'     => $manifest->universe_id,
            'tick'            => $manifest->tick,
            'seed'            => $manifest->seed,
            'elapsed_ms'      => $manifest->elapsed_ms,
            'engines_ran'     => $manifest->engines_ran,
            'engines_skipped' => $manifest->engines_skipped,
            'effects_count'   => count($manifest->effects ?? []),
            'events_count'    => count($manifest->events ?? []),
            'events'          => $manifest->events,
            'effects'         => $manifest->effects,
            'created_at'      => $manifest->created_at,
        ]);
    }

    /**
     * GET /api/worldos/universes/{id}/audit
     * Returns a paginated list of tick manifests for a universe.
     */
    public function index(int $id, Request $request): JsonResponse
    {
        $limit = min((int)$request->query('limit', 20), 100);

        $manifests = TickManifest::where('universe_id', $id)
            ->orderByDesc('tick')
            ->limit($limit)
            ->get(['id', 'universe_id', 'tick', 'seed', 'elapsed_ms', 'engines_ran', 'engines_skipped', 'created_at']);

        return response()->json([
            'data' => $manifests->map(fn($m) => [
                'tick'            => $m->tick,
                'seed'            => $m->seed,
                'elapsed_ms'      => $m->elapsed_ms,
                'engines_ran'     => count($m->engines_ran ?? []),
                'engines_skipped' => count($m->engines_skipped ?? []),
                'created_at'      => $m->created_at,
            ]),
        ]);
    }

    /**
     * POST /api/worldos/universes/{id}/audit/{tick}/replay
     * Triggers a deterministic replay of the specified tick.
     */
    public function replay(int $id, int $tick): JsonResponse
    {
        $result = $this->replayService->replay($id, $tick);

        $status = ($result['ok'] ?? false) ? 200 : 422;

        return response()->json($result, $status);
    }
}


