<?php

namespace App\Modules\Simulation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Core\Services\CausalExportService;

class CausalityController extends Controller
{
    /**
     * Export the causal graph for a specific universe.
     * GET /api/worldos/universes/{id}/causality/export
     */
    public function exportGraph(string $id, Request $request, CausalExportService $exportService)
    {
        $universe = Universe::findOrFail((int) $id);
        $limit  = (int) $request->query('limit', 100);
        $cursor = $request->has('cursor') ? (int) $request->query('cursor') : null;
        
        $graphData = $exportService->exportForUniverse($universe, $limit, $cursor);
        
        return response()->json($graphData);
    }
}


