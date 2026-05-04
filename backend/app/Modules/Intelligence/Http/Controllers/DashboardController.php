<?php

namespace App\Modules\Intelligence\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Modules\Intelligence\Services\Dashboard\StateMetricsService;
use App\Modules\Intelligence\Services\Dashboard\AttractorMetricsService;
use App\Modules\Intelligence\Services\Dashboard\ArchetypeMetricsService;
use App\Modules\Intelligence\Services\Dashboard\RiskMetricsService;
use App\Modules\Intelligence\Services\Dashboard\ModelEvolutionMetricsService;
use App\Modules\Intelligence\Services\Lab\ControlEngine;

class DashboardController extends Controller
{
    public function __construct(
        private StateMetricsService $stateService,
        private AttractorMetricsService $attractorService,
        private ArchetypeMetricsService $archetypeService,
        private RiskMetricsService $riskService,
        private ModelEvolutionMetricsService $modelEvolutionService,
        private ControlEngine $controlEngine
    ) {}

    public function state(Request $request): JsonResponse
    {
        $universeId = $request->query('universe_id');
        $id = $universeId !== null && $universeId !== '' ? (int) $universeId : null;
        return response()->json($this->stateService->getMacroState($id));
    }

    public function attractors(): JsonResponse
    {
        return response()->json($this->attractorService->getAttractorMap());
    }

    public function evolution(): JsonResponse
    {
        return response()->json($this->archetypeService->getEvolutionMetrics());
    }

    public function risks(): JsonResponse
    {
        return response()->json($this->riskService->getRiskMonitor());
    }

    public function intelligence(): JsonResponse
    {
        return response()->json($this->modelEvolutionService->getIntelligenceMetrics());
    }

    public function intervene(Request $request): JsonResponse
    {
        $state = $request->input('state', []);
        if (empty($state)) {
            $macro = $this->stateService->getMacroState();
            $state = [
                'knowledge' => $macro['tech'] ?? 0.5,
                'stability' => $macro['stability'] ?? 0.5,
                'coercion' => $macro['coercion'] ?? 0.5,
            ];
        }

        $result = $this->controlEngine->searchOptimalGovernance($state);

        return response()->json($result);
    }
}
