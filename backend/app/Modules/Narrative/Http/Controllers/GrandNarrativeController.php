<?php

namespace App\Modules\Narrative\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Simulation\Services\Narrative\GrandNarrativeService;
use Illuminate\Http\JsonResponse;

class GrandNarrativeController extends Controller
{
    public function __construct(protected GrandNarrativeService $narrativeService) {}

    /**
     * Get the grand narrative report for a universe.
     */
    public function show(int $universeId): JsonResponse
    {
        try {
            $data = $this->narrativeService->generateReport($universeId);
            return response()->json($data);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Universe not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

