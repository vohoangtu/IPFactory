<?php

namespace App\Modules\Simulation\Core\Supervisor\Contracts;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;

/**
 * Handler run after snapshot is persisted (LEVEL 7: cognitive, collapse, social, demographic, knowledge, civilization discovery, self-improving, rule VM).
 */
interface PostSnapshotHandlerInterface
{
    public function handle(Universe $universe, UniverseSnapshot $snapshot): void;
}

