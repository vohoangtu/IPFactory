<?php

namespace App\Modules\Knowledge\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Intelligence\Models\Actor;
use App\Modules\Knowledge\Services\WikiEngineService;
use App\Modules\Simulation\Services\Cosmology\AxiomRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WikiController extends Controller
{
    public function __construct(
        protected WikiEngineService $wikiEngine,
        protected AxiomRegistry $axiomRegistry
    ) {}

    /**
     * Tìm kiếm Wiki
     */
    public function search(Request $request, int $universeId): JsonResponse
    {
        $query = $request->get('q', '');
        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $results = $this->wikiEngine->search($query, $universeId);
        return response()->json(['data' => $results]);
    }

    /**
     * Chi tiết một hằng số (Axiom)
     */
    public function axiom(string $axiomId, int $universeId): JsonResponse
    {
        $axiom = $this->axiomRegistry->find($axiomId);
        if (!$axiom) {
            return response()->json(['message' => 'Axiom not found'], 404);
        }

        $driftLogs = $this->wikiEngine->getAxiomDriftLogs($axiomId, $universeId);

        return response()->json([
            'data' => [
                'axiom' => $axiom,
                'drift_logs' => $driftLogs,
            ]
        ]);
    }

    /**
     * Chi tiết một nhân vật (Actor) với liên kết song song
     */
    public function actor(int $actorId, int $universeId): JsonResponse
    {
        $actor = Actor::where('universe_id', $universeId)->findOrFail($actorId);
        
        // Auto-link biography
        $actor->biography = $this->wikiEngine->autoLink($actor->biography ?? '', $universeId);
        
        // Resolve parallel versions
        $parallelVersions = $this->wikiEngine->resolveParallelIdentities($actor);

        return response()->json([
            'data' => [
                'actor' => $actor,
                'parallel_versions' => $parallelVersions,
            ]
        ]);
    }

    /**
     * Lấy danh sách tất cả Axioms
     */
    public function axioms(): JsonResponse
    {
        return response()->json([
            'data' => $this->axiomRegistry->getAll()
        ]);
    }

    /**
     * Tìm kiếm các phiên bản song song của một identity
     */
    public function resolveIdentity(int $actorId): JsonResponse
    {
        $actor = Actor::findOrFail($actorId);
        $parallelVersions = $this->wikiEngine->resolveParallelIdentities($actor);
        
        $mapped = $parallelVersions->map(function($p) {
            return [
                'universe_id' => (string)$p->universe_id,
                'actor_id' => (string)$p->id,
                'name' => $p->name,
                'role' => $p->role,
                'similarity_score' => 1.0, // Default for exact match
                'status' => $p->is_alive ? 'active' : 'dormant'
            ];
        });

        return response()->json([
            'data' => $mapped
        ]);
    }
}
