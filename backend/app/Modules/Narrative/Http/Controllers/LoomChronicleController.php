<?php

namespace App\Modules\Narrative\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Narrative\Models\Chronicle;
use Illuminate\Http\JsonResponse;

class LoomChronicleController extends Controller
{
    /**
     * Tương đương: GET /api/loom/v1/narrative/chronicles
     * Params: world_id, universe_id, tick_start, tick_end, event_types (comma separated), per_page
     */
    public function index(Request $request): JsonResponse
    {
        $query = Chronicle::query();

        if ($request->has('universe_id')) {
            $query->where('universe_id', $request->input('universe_id'));
        } elseif ($request->has('world_id')) {
            $query->whereHas('universe', function ($q) use ($request) {
                $q->where('world_id', $request->input('world_id'));
            });
        }

        if ($request->has('tick_start')) {
            $query->where('from_tick', '>=', $request->input('tick_start'));
        }

        if ($request->has('tick_end')) {
            $query->where('to_tick', '<=', $request->input('tick_end'));
        }

        if ($request->has('event_types')) {
            $types = explode(',', $request->input('event_types'));
            $query->whereIn('type', $types);
        }

        $query->orderBy('from_tick', 'asc');

        $perPage = $request->input('per_page', 100);
        $chronicles = $query->paginate($perPage);

        // Đảm bảo raw_payload có mặt để Loom phân tích sâu
        $chronicles->getCollection()->transform(function ($item) {
            return $item;
        });

        return response()->json($chronicles);
    }
}

