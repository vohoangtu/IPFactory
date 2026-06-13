<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Hợp đồng cho StateExtractorDSL (module Narrative) — phá cycle Narrative ⇄ Intelligence (P0-6).
 */
interface StateExtractorDslInterface
{
    public function extractContext(int $universeId, int $tick, array $stateVector, array $metrics = []): array;
}
