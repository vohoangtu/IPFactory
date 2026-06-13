<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;

/**
 * Hợp đồng cho ImplicitOrchestratorService (module Simulation) — phá cycle Simulation ⇄ Narrative (P0-6).
 */
interface ImplicitOrchestratorServiceInterface
{
    public function spawnUniverse(
        World $world,
        ?int $parentUniverseId = null,
        ?int $sagaId = null,
        ?array $branchPayload = null
    ): Universe;
}
