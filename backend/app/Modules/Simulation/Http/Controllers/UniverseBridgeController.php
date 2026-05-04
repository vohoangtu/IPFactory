<?php

namespace App\Modules\Simulation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\UniverseBridge;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UniverseBridgeController extends Controller
{
    /**
     * List all bridges for a specific universe (source OR target).
     */
    public function index(int $universeId): JsonResponse
    {
        $bridgeScope = UniverseBridge::with(['sourceUniverse:id,name', 'targetUniverse:id,name'])
            ->where('source_universe_id', $universeId)
            ->orWhere('target_universe_id', $universeId)
            ->get();

        return response()->json($bridgeScope);
    }

    /**
     * Create a new bridge originating from the specified universe.
     */
    public function store(Request $request, int $universeId): JsonResponse
    {
        $request->validate([
            'target_universe_id' => 'required|exists:universes,id|different:source_universe_id',
            'bridge_type' => 'required|string|in:causal,resonance,bleed',
            'resonance_level' => 'required|numeric|min:0|max:1',
        ]);

        if ((int)$request->target_universe_id === $universeId) {
            return response()->json(['message' => 'Cannot bridge a universe to itself'], 422);
        }

        // Check if bridge already exists
        $existing = UniverseBridge::where('source_universe_id', $universeId)
            ->where('target_universe_id', $request->target_universe_id)
            ->first();

        if ($existing) {
            $existing->update([
                'bridge_type' => $request->bridge_type,
                'resonance_level' => $request->resonance_level,
                'is_active' => true,
            ]);
            $bridge = $existing;
        } else {
            $bridge = UniverseBridge::create([
                'source_universe_id' => $universeId,
                'target_universe_id' => $request->target_universe_id,
                'bridge_type' => $request->bridge_type,
                'resonance_level' => $request->resonance_level,
                'is_active' => true,
            ]);
        }

        return response()->json($bridge->load(['sourceUniverse', 'targetUniverse']), 201);
    }

    /**
     * Get convergence map (bridges and scores).
     */
    public function convergenceMap(int $universeId): JsonResponse
    {
        $bridges = UniverseBridge::with(['targetUniverse:id,name,entropy,status'])
            ->where('source_universe_id', $universeId)
            ->get();

        return response()->json([
            'universe_id' => $universeId,
            'bridges' => $bridges,
        ]);
    }

    /**
     * Remove a bridge by its ID.
     */
    public function destroy(int $universeId, int $bridgeId): JsonResponse
    {
        $bridge = UniverseBridge::where('id', $bridgeId)
            ->where(function ($query) use ($universeId) {
                $query->where('source_universe_id', $universeId)
                      ->orWhere('target_universe_id', $universeId);
            })->first();

        if (!$bridge) {
            return response()->json(['message' => 'Bridge not found or access denied'], 404);
        }

        $bridge->delete();
        return response()->json(['message' => 'Bridge destroyed']);
    }
}

