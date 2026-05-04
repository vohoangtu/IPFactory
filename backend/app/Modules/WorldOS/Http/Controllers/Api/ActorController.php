<?php

namespace App\Modules\WorldOS\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Intelligence\Models\Actor;
use App\Modules\Intelligence\Models\ActorEvent;
use App\Modules\Intelligence\Models\AgentDecision;
use App\Modules\Institutions\Models\SupremeEntity;
use App\Modules\Intelligence\Actions\GetUniverseActorsAction;
use App\Modules\WorldOS\Http\Resources\ActorDecisionResource;
use App\Modules\WorldOS\Http\Resources\ActorDetailResource;
use App\Modules\WorldOS\Http\Resources\ActorEventResource;
use App\Modules\WorldOS\Http\Resources\ActorSummaryResource;
use App\Modules\WorldOS\Http\Resources\SupremeEntityResource;
use App\Modules\Narrative\Services\NarrativeLoomService;
use Illuminate\Http\JsonResponse;

class ActorController extends Controller
{
    public function index(int $id, GetUniverseActorsAction $action): JsonResponse
    {
        $actors = $action->execute($id);

        return ActorSummaryResource::collection($actors)->response();
    }

    public function show(int $id): JsonResponse
    {
        $actor = Actor::with(['supremeEntity', 'events'])->find($id);
        if (! $actor) {
            return response()->json(['message' => 'Actor not found'], 404);
        }

        return (new ActorDetailResource($actor))->response();
    }

    public function events(int $id): JsonResponse
    {
        $events = ActorEvent::where('actor_id', $id)->orderBy('tick')->get();

        return ActorEventResource::collection($events)->response();
    }

    public function decisions(int $id): JsonResponse
    {
        $decisions = AgentDecision::where('actor_id', $id)
            ->orderByDesc('tick')
            ->limit(50)
            ->get();

        return ActorDecisionResource::collection($decisions)->response();
    }

    public function supremeEntities(int $id): JsonResponse
    {
        $entities = SupremeEntity::where('universe_id', $id)
            ->orderBy('entity_type')
            ->orderByDesc('power_level')
            ->get();

        return SupremeEntityResource::collection($entities)->response();
    }

    public function mindMeld(int $id, NarrativeLoomService $loomService): JsonResponse
    {
        $actor = Actor::find($id);
        if (!$actor) {
            return response()->json(['message' => 'Actor not found'], 404);
        }

        // Tái tạo Context: Dữ liệu hiện tại của vũ trụ + Tiểu sử Actor
        $requestData = [
            'actor_id' => $actor->id,
            'universe_id' => $actor->universe_id,
            'state' => ['health' => 100, 'stress' => 50], // Mock parameters for AI
        ];

        // Lọc qua Narrative Loom 
        $response = $loomService->getActorIntent($requestData);

        if (isset($response['ok']) && !$response['ok']) {
            return response()->json(['message' => $response['error'] ?? 'Mind meld failed'], 503);
        }

        return response()->json([
            'action' => $response['action'] ?? 'Đang trong trạng thái vô định nội tâm...',
            'confidence' => $response['confidence'] ?? 50.0
        ]);
    }
}
