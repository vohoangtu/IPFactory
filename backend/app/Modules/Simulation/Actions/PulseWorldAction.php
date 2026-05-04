<?php

namespace App\Modules\Simulation\Actions;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Services\Meta\UniverseRuntimeService;
use App\Modules\Simulation\Services\Meta\TemporalSyncService;
use App\Modules\Simulation\Services\Ecology\AnomalyGeneratorService;

class PulseWorldAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected \App\Modules\Simulation\Services\Meta\UniverseRuntimeService $runtime,
        protected \App\Modules\Simulation\Services\Meta\WorldRegulatorEngine $autonomicEngine,
        protected \App\Modules\Simulation\Services\Meta\TemporalSyncService $temporalSync,
        protected \App\Modules\Simulation\Services\Ecology\AnomalyGeneratorService $anomalyGenerator,
        protected \App\Modules\Simulation\Core\Domain\Services\UniverseRebirthDomainService $rebirthService
    ) {}

    /**
     * Pulse World: advance all active universes in the world.
     */
    public function execute(World $world, int $ticksPerUniverse): array
    {
        $results = [];

        // Phase: Primordial Bootstrap — detect restarting universes and rebirth them
        $this->processRestartingUniverses($world);

        $universes = Universe::where('world_id', $world->id)
            ->where('status', 'active')
            ->get();

        // Phase 96: Absolute Chronos (§V21)
        // Ensure all universes are locked to the world's master clock
        $this->temporalSync->advanceGlobalClock($world, $ticksPerUniverse);

        foreach ($universes as $universe) {
            $results[$universe->id] = $this->runtime->advance($universe->id, $ticksPerUniverse);
            $this->temporalSync->synchronize($universe);

            // Phase 109 & 110: Emergent Phenomena & Multiversal Bleed (§V25)
            if ($world->is_chaotic && rand(0, 1000) < 5) {
                // Determine if it's a cross-universe bleed or local anomaly
                if ($universes->count() > 1 && rand(0, 1) === 1) {
                    // Multiversal Bleed: Anomaly happens in a DIFFERENT random universe belonging to this world
                    $targetBleed = $universes->except($universe->id)->random();
                    $this->anomalyGenerator->spawnAnomaly($targetBleed);
                    \Log::info("MULTIVERSAL BLEED: Universe #{$universe->id} leaked an anomaly into Universe #{$targetBleed->id}.");
                } else {
                    $this->anomalyGenerator->spawnAnomaly($universe);
                }
            }
        }

        // Run World Autonomic Engine after pulsing all universes
        $this->autonomicEngine->process($world);

        return $results;
    }

    /**
     * Hỗn Nguyên Phase: Detect restarting universes and perform primordial bootstrap.
     *
     * After Eschaton, a universe enters 'restarting' status.
     * This method creates a primordial snapshot (tabula rasa) for the new epoch,
     * records the rebirth in Chronicle, then transitions back to 'active'.
     */
    protected function processRestartingUniverses(World $world): void
    {
        $restartingUniverses = Universe::where('world_id', $world->id)
            ->where('status', 'restarting')
            ->get();

        foreach ($restartingUniverses as $universe) {
            $this->rebirthService->rebirthUniverse($universe);
        }
    }
}



