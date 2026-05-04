<?php

namespace App\Modules\Simulation\Core\Contracts;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;

/**
 * Archive snapshot blob to cold storage (S3/MinIO). Doc §10, RÀ_SOÁT_TMP mục 7.
 */
interface SnapshotArchiveInterface
{
    /**
     * Optionally archive snapshot to object storage. No-op when driver is null.
     */
    public function archive(Universe $universe, UniverseSnapshot $snapshot): void;
}

