<?php

namespace App\Modules\Simulation\Core\Supervisor\Handlers;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Supervisor\Contracts\PostSnapshotHandlerInterface;
use App\Modules\Simulation\Services\Society\DemographicRatesService;

final class DemographicRatesPostSnapshotHandler implements PostSnapshotHandlerInterface
{
    public function __construct(
        private readonly DemographicRatesService $demographicRatesService,
    ) {}

    public function handle(Universe $universe, UniverseSnapshot $snapshot): void
    {
        $this->demographicRatesService->evaluate($universe, (int) $snapshot->tick);
    }
}

