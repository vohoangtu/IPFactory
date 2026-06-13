<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Hợp đồng cho NarrativeGeneratorService (module Narrative) — phá cycle Narrative ⇄ Intelligence (P0-6).
 */
interface NarrativeGeneratorServiceInterface
{
    public function generateLifeEvent(string $name, string $archetype, array $traits = [], array $config = []): string;
}
