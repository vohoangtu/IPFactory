<?php

namespace App\Modules\Simulation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Simulation\Services\RuleEngine\RuleGraphService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RuleDebuggerController extends Controller
{
    public function __construct(
        protected RuleGraphService $graphService
    ) {}

    /**
     * Get the global rule dependency graph.
     */
    public function getGraph(): JsonResponse
    {
        $rulesDir = resource_path('worldos_rules');
        $graph = $this->graphService->buildGraph($rulesDir);
        
        return response()->json($graph);
    }

    /**
     * Get execution logs for a specific universe (place holder for execution debugging).
     */
    public function getExecutionStats(int $universeId): JsonResponse
    {
        // This would integrate with a logging system to show which rules are "hot"
        return response()->json([
            'universe_id' => $universeId,
            'hot_rules' => [],
            'last_evaluated_tick' => 0
        ]);
    }
}



