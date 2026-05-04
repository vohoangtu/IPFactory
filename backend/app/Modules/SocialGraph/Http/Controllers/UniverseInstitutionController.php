<?php

namespace App\Modules\SocialGraph\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Institutions\Models\InstitutionalEntity;
use Illuminate\Http\JsonResponse;

class UniverseInstitutionController extends Controller
{
    /**
     * Lấy danh sách các định chế (Institutions) đang hoạt động trong vũ trụ.
     */
    public function index(int $universeId): JsonResponse
    {
        $institutions = InstitutionalEntity::where('universe_id', $universeId)
            ->whereNull('collapsed_at_tick')
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $institutions->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'type' => $item->entity_type,
                    'capacity' => round($item->org_capacity, 2),
                    'legitimacy' => round($item->legitimacy, 2),
                    'ideology' => $item->ideology_vector,
                    'influence' => $item->influence_map,
                    'memory' => round($item->institutional_memory, 2),
                ];
            }),
        ]);
    }
}

