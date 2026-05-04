<?php

namespace App\Modules\Simulation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Narrative\Actions\CelestialEngineeringAction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * CelestialEngineeringController: The Architect's Interface for Edicts and Axiom Shifts (§V12).
 * Allows direct intervention with immediate feedback.
 */
class CelestialEngineeringController extends Controller
{
    public function __construct(
        protected CelestialEngineeringAction $engineering
    ) {}

    /**
     * Thực thi một Can thiệp Thiên thể (Edict hoặc Axiom Shift).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'world_id' => 'required|exists:worlds,id',
            'type' => 'required|in:axiom_shift,macro_edict',
            'payload' => 'required|array',
            'payload.name' => 'required_if:type,macro_edict|string',
            'payload.sci_impact' => 'nullable|numeric|min:-1|max:1',
            'payload.entropy_impact' => 'nullable|numeric|min:-1|max:1',
        ]);

        $this->engineering->executeMacro(
            $validated['world_id'],
            $validated['type'],
            $validated['payload']
        );

        return response()->json([
            'message' => 'Celestial intervention successful. Ripple effects propagated throughout the multiverse.',
            'status' => 'success'
        ]);
    }
}

