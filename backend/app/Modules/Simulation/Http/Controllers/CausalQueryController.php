<?php

namespace App\Modules\Simulation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Contracts\CausalityGraphServiceInterface;
use Illuminate\Http\JsonResponse;

class CausalQueryController extends Controller
{
    public function __construct(
        protected CausalityGraphServiceInterface $causalityGraph
    ) {}

    /**
     * Get the most recent semantic causal links across the simulation.
     */
    public function getRecentLinks(): JsonResponse
    {
        $limit = (int) request()->query('limit', 20);
        $links = $this->causalityGraph->getRecentLinks($limit);

        return response()->json([
            'ok' => true,
            'links' => $links
        ]);
    }
}
