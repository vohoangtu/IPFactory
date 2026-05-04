<?php

namespace App\Modules\Simulation\Core\Supervisor\Handlers;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Services\Ecology\UrbanStressAgricultureService;
use App\Modules\Simulation\Core\Supervisor\Contracts\PostSnapshotHandlerInterface;
use Illuminate\Support\Facades\Config;

final class UrbanStressAgriculturePostSnapshotHandler implements PostSnapshotHandlerInterface
{
    public function __construct(
        private readonly UrbanStressAgricultureService $urbanStressAgricultureService
    ) {}

    public function handle(Universe $universe, UniverseSnapshot $snapshot): void
    {
        if (! Config::get('worldos.urban_stress_agriculture.enabled', true)) {
            return;
        }

        // Skip when Rust is authoritative and agriculture fields already present
        if (Config::get('worldos_simulation.simulation.rust_authoritative', true)) {
            $sv = is_array($snapshot->state_vector) ? $snapshot->state_vector : [];
            if (isset($sv['agriculture_capacity'])) {
                return;
            }
        }

        $this->urbanStressAgricultureService->update($universe);
    }
}

