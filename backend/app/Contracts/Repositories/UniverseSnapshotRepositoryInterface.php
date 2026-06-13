<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;

/**
 * Hợp đồng cho UniverseSnapshotRepository (module Simulation) — phá cycle Simulation ⇄ Narrative (P0-6).
 */
interface UniverseSnapshotRepositoryInterface
{
    public function save(Universe $universe, array $snapshot): UniverseSnapshot;

    public function getAtTick(int $universeId, int $tick): ?UniverseSnapshot;

    public function getLatest(int $universeId): ?UniverseSnapshot;
}
