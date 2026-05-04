<?php

namespace App\Modules\Narrative\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Services\Ecology\ZenithMetricsService;
use App\Modules\Simulation\Core\Runtime\State\StateManager;
use Illuminate\Http\JsonResponse;

/**
 * Phase 71: Zenith Controller 💎🛰️
 * 
 * Cung cấp API báo cáo tối thượng cho WorldOS V10 Dashboard.
 */
class ZenithController extends Controller
{
    public function __construct(
        private readonly ZenithMetricsService $metricsService,
        private readonly StateManager $stateManager
    ) {}

    /**
     * Get the supreme Zenith Report for a universe.
     */
    public function show(string $id): JsonResponse
    {
        $universe = Universe::find((int) $id);
        if (!$universe) {
            return response()->json(['message' => 'Universe not found'], 404);
        }

        // Đảm bảo StateManager có dữ liệu
        $state = $this->stateManager->get();
        if (!$state) {
            $state = $this->stateManager->load($universe);
        }

        $report = $this->metricsService->getZenithReport($state);

        return response()->json([
            'universe' => [
                'id' => $universe->id,
                'name' => $universe->name,
                'tick' => $universe->current_tick,
            ],
            'zenith_report' => $report,
            'timestamp' => now()->toIso8601String()
        ]);
    }
}



