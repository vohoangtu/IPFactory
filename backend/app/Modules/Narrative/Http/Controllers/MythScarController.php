<?php

namespace App\Modules\Narrative\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Narrative\Models\MythScar;
use Illuminate\Http\JsonResponse;

class MythScarController extends Controller
{
    /**
     * Get all myth scars for a specific universe.
     */
    public function index(string $universeId): JsonResponse
    {
        $id = (int) $universeId;
        
        $scars = MythScar::where('universe_id', $id)
            ->orderByDesc('created_at_tick')
            ->get();
            
        return response()->json($scars);
    }
}

