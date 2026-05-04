<?php

namespace App\Modules\Simulation\Services\Cosmology;

use App\Modules\World\Models\Demiurge;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Services\Core\ImplicitOrchestratorService;
use Illuminate\Support\Facades\Log;

/**
 * HeatDeathService: Monitors and manages total multiverse energy (§V17).
 * Prevents 'Cosmic Inflation' by triggering Big Bangs when Essence is too high.
 */
class HeatDeathService
{
    public function __construct(
        protected ImplicitOrchestratorService $orchestrator
    ) {}

    /**
     * Check the total divine energy in the system.
     */
    public function monitor(): void
    {
        $totalEssence = Demiurge::sum('essence_pool');
        $activeUniverses = Universe::where('status', 'active')->count();

        // Threshold: 20 Essence per active universe
        $threshold = $activeUniverses * 20;

        if ($totalEssence > $threshold && $totalEssence > 100) {
            $this->triggerBigBang($totalEssence - $threshold);
        }
    }

    protected function triggerBigBang(float $excessEnergy): void
    {
        Log::emergency("COSMIC INFLATION: Total Essence has exceeded safety limits ({$excessEnergy} excess).");

        // Forcefully tax all demiurges
        Demiurge::query()->update(['essence_pool' => \DB::raw('essence_pool * 0.5')]);

        // Pick a World to spawn a new Universe in (for simplicity, the first one)
        $world = \App\Modules\World\Models\World::first();
        if ($world) {
            $universe = $this->orchestrator->spawnUniverse($world, null, null);
            Log::info("BIG BANG: New Universe #{$universe->id} created to absorb excess divine energy.");
        }
    }
}

