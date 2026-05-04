<?php

namespace App\Modules\Simulation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Contracts\GraphProviderInterface;
use Illuminate\Http\JsonResponse;

class UniverseGraphController extends Controller
{
    public function __construct(
        protected GraphProviderInterface $graphProvider
    ) {}

    /**
     * Lấy dữ liệu Đồ thị (Nodes & Edges) cho một Universe.
     */
    public function show(int $id): JsonResponse
    {
        return response()->json([
            'universe_id' => $id,
            'nodes' => $this->graphProvider->getUniverseNodes($id),
            'edges' => $this->graphProvider->getUniverseEdges($id),
        ]);
    }
}
