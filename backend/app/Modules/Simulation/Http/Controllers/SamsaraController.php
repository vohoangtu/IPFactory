<?php

namespace App\Modules\Simulation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Simulation\Services\Cosmology\SamsaraService;
use Illuminate\Http\JsonResponse;

class SamsaraController extends Controller
{
    public function __construct(protected SamsaraService $samsaraService) {}

    /**
     * Get the transmigration path for a legendary agent.
     */
    public function show(int $agentId): JsonResponse
    {
        try {
            $data = $this->samsaraService->getPath($agentId);
            return response()->json($data);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Agent not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
