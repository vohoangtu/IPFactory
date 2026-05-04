<?php

namespace App\Modules\WorldOS\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\World\Models\World;
use App\Modules\Simulation\Models\UniverseInteraction;
use App\Modules\Simulation\Models\CausalTrajectory;
use Illuminate\Http\JsonResponse;

class WorldController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(World::with('universes')->get());
    }

    public function interactions(int $id): JsonResponse
    {
        return response()->json(
            UniverseInteraction::where('universe_id', $id)
                ->latest()
                ->take(50)
                ->get()
        );
    }

    public function trajectories(int $id): JsonResponse
    {
        return response()->json(
            CausalTrajectory::where('universe_id', $id)->get()
        );
    }
}
