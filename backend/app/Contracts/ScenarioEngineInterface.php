<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Modules\World\Models\Universe;

/**
 * Hợp đồng cho ScenarioEngine (module Simulation) — phá cycle Simulation ⇄ Narrative (P0-6).
 */
interface ScenarioEngineInterface
{
    public function launch(Universe $universe, string $scenarioId): array;
}
