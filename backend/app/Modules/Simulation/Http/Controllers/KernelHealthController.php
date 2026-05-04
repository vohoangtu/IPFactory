<?php
declare(strict_types=1);

namespace App\Modules\Simulation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Simulation\Core\Services\TickMetricsService;
use Illuminate\Http\JsonResponse;

class KernelHealthController extends Controller
{
    public function __construct(
        private readonly TickMetricsService $metricsService
    ) {}

    /**
     * Get simulation health and performance telemetry for a universe.
     */
    public function show(int $universeId): JsonResponse
    {
        $history = $this->metricsService->getHistory($universeId);
        $aggregate = $this->metricsService->getAggregateHealth($universeId);

        return response()->json([
            'universe_id' => $universeId,
            'health' => $aggregate,
            'history' => $history,
        ]);
    }
}

