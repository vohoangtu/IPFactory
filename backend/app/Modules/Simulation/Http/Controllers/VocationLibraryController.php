<?php

namespace App\Modules\Simulation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Simulation\Vocation\Contracts\VocationRepositoryInterface;
use App\Modules\Simulation\Vocation\Http\Resources\VocationResource;
use Illuminate\Http\JsonResponse;

class VocationLibraryController extends Controller
{
    public function __construct(
        private VocationRepositoryInterface $repository
    ) {}

    /**
     * Get all vocation definitions.
     */
    public function index(): JsonResponse
    {
        $vocations = $this->repository->getAll();

        return response()->json([
            'success' => true,
            'data' => VocationResource::collection($vocations)
        ]);
    }

    /**
     * Get a single vocation by ID (string or int handled by repo).
     */
    public function show(string $id): JsonResponse
    {
        $vocation = $this->repository->findById((int)$id);

        if (!$vocation) {
            return response()->json(['success' => false, 'message' => 'Vocation not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => VocationResource::make($vocation)
        ]);
    }
}
