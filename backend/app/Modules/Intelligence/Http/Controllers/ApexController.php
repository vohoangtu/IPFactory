<?php

namespace App\Modules\Intelligence\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Actions\ApexObserverAction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Phase 72: Apex Controller 👁️🛰️
 * 
 * Endpoint thực thi các lệnh can thiệp tối thượng (Apex Commands).
 */
class ApexController extends Controller
{
    public function __construct(
        private readonly ApexObserverAction $apexAction
    ) {}

    /**
     * Execute an Apex command on a universe.
     */
    public function command(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'command' => 'required|string|in:LOCK_TRAJECTORY,COLLAPSE_WAVEFUNCTION,DILATE_TIME,INJECT_REALITY_GLITCH',
            'payload' => 'nullable|array'
        ]);

        $universe = Universe::find((int) $id);
        if (!$universe) {
            return response()->json(['message' => 'Universe not found'], 404);
        }

        $result = $this->apexAction->execute(
            $universe,
            $request->input('command'),
            $request->input('payload', [])
        );

        return response()->json($result, $result['ok'] ? 200 : 400);
    }
}


