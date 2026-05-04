<?php

namespace App\Modules\Simulation\Actions;

use App\Modules\Narrative\Models\Chronicle;
use Illuminate\Support\Facades\Log;

/**
 * DecayScarsAction - V10: Entropy of Memory
 * 
 * Implements historical decay: older memories fade over time.
 * If importance drops below threshold, the scar is removed (Forgotten).
 */
class DecayScarsAction
implements \App\Contracts\ActionInterface {
    private const DECAY_FACTOR = 0.95; // 5% importance decay per cycle
    private const REMOVAL_THRESHOLD = 0.15;

    /**
     * Apply decay to all chronicles of a universe.
     */
    public function handle(int $universeId): void
    {
        $scars = Chronicle::where('universe_id', $universeId)
            ->where('importance', '>', 0)
            ->get();

        $fadedCount = 0;
        $deletedCount = 0;

        foreach ($scars as $scar) {
            // Keep high-importance markers (Epoch markers) longer
            $newImportance = $scar->importance * self::DECAY_FACTOR;
            
            if ($newImportance < self::REMOVAL_THRESHOLD) {
                $scar->delete();
                $deletedCount++;
            } else {
                $scar->importance = $newImportance;
                $scar->save();
                $fadedCount++;
            }
        }

        if ($deletedCount > 0 || $fadedCount > 0) {
            Log::info("DecayScarsAction: Processed memory decay for Universe {$universeId}. Faded: {$fadedCount}, Forgotten: {$deletedCount}");
        }
    }
}
