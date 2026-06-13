<?php

namespace App\Modules\Narrative\Services;
use App\Contracts\NarrativeGeneratorServiceInterface;

class NarrativeGeneratorService implements NarrativeGeneratorServiceInterface
{
    public function generate(): string { return ""; }

    public function generateLifeEvent(string $name, string $archetype, array $traits = [], array $config = []): string
    {
        return "{$name} experienced a pivotal moment that shaped their destiny.";
    }
}
