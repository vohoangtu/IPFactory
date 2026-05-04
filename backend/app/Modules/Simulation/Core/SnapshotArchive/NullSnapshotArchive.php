<?php

namespace App\Modules\Simulation\Core\SnapshotArchive;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Contracts\SnapshotArchiveInterface;

final class NullSnapshotArchive implements SnapshotArchiveInterface
{
    public function archive(Universe $universe, UniverseSnapshot $snapshot): void
    {
        // no-op
    }
}

