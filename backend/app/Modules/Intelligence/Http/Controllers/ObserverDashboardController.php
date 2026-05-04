<?php

namespace App\Modules\Intelligence\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\World\Models\Demiurge;
use App\Modules\Intelligence\Models\LegendaryAgent;
use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Services\EtherealOmenService;
use Illuminate\Http\JsonResponse;

/**
 * ObserverDashboardController: The Architect's view (§V18).
 */
class ObserverDashboardController extends Controller
{
    public function __construct(
        protected EtherealOmenService $omenService
    ) {}

    /**
     * Get the global status of the Multiverse Pantheon.
     */
    public function getStatus(): JsonResponse
    {
        // Phase 90 & 93: From Gaze to Resonance (§V19, §V20)
        // No longer updating last_observed_at here as the system is autonomous.

        $demiurges = Demiurge::all()->map(function ($d) {
            return [
                'id' => $d->id,
                'name' => $d->name,
                'intention' => $d->intention_type,
                'will_power' => $d->will_power,
                'essence' => $d->essence_pool,
                'followers' => LegendaryAgent::where('alignment_id', $d->id)->count(),
            ];
        });

        // Pick one active universe for summary
        $summaryUniverse = Universe::where('status', 'active')->first();
        $currentOmen = $summaryUniverse ? $this->omenService->generateInternalOmen($summaryUniverse) : null;

        return response()->json([
            'pantheon' => $demiurges,
            'multiverse' => [
                'active_universes' => Universe::where('status', 'active')->count(),
                'total_essence' => $demiurges->sum('essence'),
                'current_omen' => $currentOmen,
                'autonomy_status' => 'SINGULARITY_RESONANCE_ACTIVE',
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}

