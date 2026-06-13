<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Modules\World\Models\World;

/**
 * Hợp đồng cho WorldAxiomAction (module Simulation) — phá cycle Simulation ⇄ Narrative (P0-6).
 */
interface WorldAxiomActionInterface
{
    public function execute(World $world, array $newAxioms): array;
}
