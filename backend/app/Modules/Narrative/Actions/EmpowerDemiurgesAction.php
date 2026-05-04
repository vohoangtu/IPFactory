<?php

namespace App\Modules\Narrative\Actions;

use App\Modules\Narrative\Contracts\DemiurgeRepositoryInterface;
use App\Modules\Intelligence\Models\LegendaryAgent;
use Illuminate\Support\Facades\Log;

/**
 * EmpowerDemiurgesAction: Scales Divine Power based on followers (§V16).
 * 'will_power' determines the frequency and impact of autonomous edicts.
 */
class EmpowerDemiurgesAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected DemiurgeRepositoryInterface $demiurgeRepository
    ) {}

    /**
     * Recalculate will_power for all active Demiurges.
     */
    public function execute(): void
    {
        $demiurges = $this->demiurgeRepository->all();

        foreach ($demiurges as $demiurge) {
            $followersCount = LegendaryAgent::where('alignment_id', $demiurge->id)->count();
            
            // Base power 100 + 50 per legend
            $newPower = 100 + ($followersCount * 50);
            
            $demiurge->will_power = $newPower;
            $this->demiurgeRepository->save($demiurge);
            
            Log::info("PAN-MULTIVERSE: Demiurge [{$demiurge->name}] now has Will Power: {$newPower} (Followers: {$followersCount})");
        }
    }
}

