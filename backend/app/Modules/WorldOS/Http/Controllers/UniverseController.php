<?php

declare(strict_types=1);

namespace App\Modules\WorldOS\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Repositories\UniverseSnapshotRepository;
use App\Modules\Simulation\Services\Meta\UniverseRuntimeService;
use App\Modules\World\Models\Universe;
use App\Modules\WorldOS\Actions\CreateGenesisUniverseAction;
use App\Modules\WorldOS\Actions\ForkUniverseAction;
use App\Modules\WorldOS\Http\Resources\BranchComparisonResource;
use App\Modules\WorldOS\Http\Resources\BranchSummaryResource;
use App\Modules\WorldOS\Http\Resources\SnapshotDetailResource;
use App\Modules\WorldOS\Http\Resources\SnapshotResource;
use App\Modules\WorldOS\Http\Resources\UniverseDetailResource;
use App\Modules\WorldOS\Http\Resources\UniverseDossierResource;
use App\Modules\WorldOS\Http\Resources\UniverseMetricsResource;
use App\Modules\WorldOS\Http\Resources\UniverseSummaryResource;
use App\Modules\WorldOS\Services\UniverseComparisonBuilder;
use App\Modules\WorldOS\Services\UniverseDossierService;
use App\Modules\WorldOS\Services\UniverseMetricsService;
use App\Modules\WorldOS\Services\UniverseRealityStateBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UniverseController extends Controller
{
    public function index(): JsonResponse
    {
        $query = Universe::with([
            'world:id,name,slug,current_genre,base_genre',
            'latestSnapshot',
        ])->withCount('childUniverses');

        if (request()->has('world_id')) {
            $query->where('world_id', (int) request('world_id'));
        }

        return UniverseSummaryResource::collection($query->get())->response();
    }

    public function show(string $id): JsonResponse
    {
        $universe = Universe::with([
            'world:id,name,slug,axiom,origin,current_genre,base_genre,is_autonomic',
            'latestSnapshot',
        ])->withCount('childUniverses')->findOrFail((int) $id);

        $universe->update(['last_observed_at' => now()]);
        $universe->refresh();
        $universe->loadMissing([
            'world:id,name,slug,axiom,origin,current_genre,base_genre,is_autonomic',
            'latestSnapshot',
        ]);

        return (new UniverseDetailResource($universe))->response();
    }

    public function metrics(string $id, UniverseMetricsService $metricsService): JsonResponse
    {
        return (new UniverseMetricsResource($metricsService->getMetrics((int) $id)))->response();
    }

    public function dossier(string $id, UniverseDossierService $dossierService): JsonResponse
    {
        return (new UniverseDossierResource($dossierService->getDossier((int) $id)))->response();
    }

    public function toggleStatus(string $id): JsonResponse
    {
        $universe = Universe::findOrFail((int) $id);
        $newStatus = $universe->status === 'active' ? 'inactive' : 'active';
        $universe->update(['status' => $newStatus]);

        return response()->json([
            'ok' => true,
            'new_status' => $newStatus,
            'data' => [
                'id' => $universe->id,
                'status' => $newStatus,
            ],
        ]);
    }

    public function snapshot(string $id, UniverseSnapshotRepository $repo): JsonResponse
    {
        $snapshot = $repo->getLatest((int) $id);
        if (! $snapshot) {
            return response()->json(['message' => 'Snapshot not found'], 404);
        }

        return (new SnapshotDetailResource($snapshot))->response();
    }

    public function snapshots(string $id): JsonResponse
    {
        $limit = (int) request()->query('limit', 50);
        $limit = $limit > 0 && $limit <= 500 ? $limit : 50;

        $rows = UniverseSnapshot::where('universe_id', (int) $id)
            ->orderByDesc('tick')
            ->limit($limit)
            ->get(['id', 'universe_id', 'tick', 'state_vector', 'entropy', 'stability_index', 'metrics', 'created_at']);

        return SnapshotResource::collection($rows)->response();
    }

    public function createSnapshot(string $id, UniverseSnapshotRepository $repo): JsonResponse
    {
        $universe = Universe::findOrFail((int) $id);
        $stateVector = is_array($universe->state_vector) ? $universe->state_vector : [];
        $snapshot = $repo->save($universe, [
            'tick' => (int) ($universe->current_tick ?? 0),
            'state_vector' => $stateVector,
            'entropy' => (float) ($universe->entropy ?? data_get($stateVector, 'entropy', 0)),
            'stability_index' => (float) ($universe->structural_coherence ?? data_get($stateVector, 'stability_index', 0)),
        ]);

        return response()->json([
            'ok' => true,
            'data' => [
                'created' => $snapshot->wasRecentlyCreated,
                'snapshot' => (new SnapshotDetailResource($snapshot))->resolve(),
            ],
        ]);
    }

    public function getSnapshot(string $snapshotId): JsonResponse
    {
        $snapshot = UniverseSnapshot::findOrFail((int) $snapshotId);

        return (new SnapshotDetailResource($snapshot))->response();
    }

    public function forks(string $id): JsonResponse
    {
        $rows = Universe::query()
            ->where('parent_universe_id', (int) $id)
            ->orderByDesc('forked_at_tick')
            ->get(['id', 'parent_universe_id', 'name', 'status', 'forked_at_tick', 'current_tick', 'created_at']);

        return BranchSummaryResource::collection($rows)->response();
    }

    public function compareFork(string $id, UniverseComparisonBuilder $builder): JsonResponse
    {
        $validated = request()->validate([
            'branch_id' => ['required', 'integer'],
        ]);

        $universe = Universe::with('latestSnapshot')->findOrFail((int) $id);
        $branch = Universe::with('latestSnapshot')
            ->where('parent_universe_id', $universe->id)
            ->findOrFail((int) $validated['branch_id']);

        return (new BranchComparisonResource($builder->buildComparison($universe, $branch)))->response();
    }

    public function fork(string $id, ForkUniverseAction $action, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tick' => 'nullable|integer|min:0',
        ]);

        $tick = (int) ($validated['tick'] ?? 0);
        $name = $request->input('name');
        $universe = Universe::findOrFail((int) $id);

        $child = $action->handle($universe, $tick, $name);
        $child->refresh();

        return response()->json([
            'ok' => true,
            'child_universe_id' => $child->id,
            'data' => [
                'child_universe_id' => $child->id,
                'branch' => (new BranchSummaryResource($child))->resolve(),
            ],
        ]);
    }

    public function update(string $id, Request $request): JsonResponse
    {
        $universe = Universe::findOrFail((int) $id);
        $universe->update($request->only(['name', 'status']));
        $universe->loadMissing([
            'world:id,name,slug,axiom,origin,current_genre,base_genre,is_autonomic',
            'latestSnapshot',
        ])->loadCount('childUniverses');

        return response()->json([
            'ok' => true,
            'data' => (new UniverseDetailResource($universe))->resolve(),
        ]);
    }

    public function store(Request $request, CreateGenesisUniverseAction $action): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'base_genre' => ['nullable', 'string'],
            'axioms' => ['nullable', 'array'],
            'initial_state' => ['nullable', 'array'],
        ]);

        $universe = $action->doExecute($validated);

        return response()->json([
            'ok' => true,
            'data' => (new UniverseDetailResource($universe))->resolve(),
        ], 201);
    }

    public function advance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'universe_id' => 'required|integer',
            'ticks' => 'sometimes|integer|min:1|max:1000',
        ]);

        $result = app(UniverseRuntimeService::class)
            ->advance((int) $validated['universe_id'], (int) ($validated['ticks'] ?? 1));

        return response()->json(['data' => $result]);
    }

    public function realityState(
        string $id,
        UniverseRealityStateBuilder $builder,
    ): JsonResponse {
        $universe = Universe::with(['latestSnapshot', 'world'])->findOrFail((int) $id);

        return response()->json($builder->build($universe, []));
    }

    public function destroy(string $id): JsonResponse
    {
        $universe = Universe::findOrFail((int) $id);

        UniverseSnapshot::where('universe_id', $universe->id)->delete();
        $universe->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Universe deleted successfully',
            'data' => [
                'id' => (int) $id,
                'deleted' => true,
            ],
        ]);
    }
}
