<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;

/**
 * Hợp đồng cho WorldEdictEngine (module Institutions).
 *
 * Đặt ở app/Contracts/ (global) để các module khác — vd Intelligence\OvermindEvolutionAction —
 * phụ thuộc vào interface thay vì class cụ thể của Institutions, phá cycle Intelligence ⇄ Institutions (P0-6).
 */
interface WorldEdictEngineInterface
{
    public function decree(Universe $universe, UniverseSnapshot $snapshot): void;

    public function activateEdict(
        Universe $universe,
        int $tick,
        array &$metrics,
        string $edictId,
        string $decreedBy,
        string $narrativeContext = ''
    ): bool;

    public function getEdictDictionary(): array;
}
