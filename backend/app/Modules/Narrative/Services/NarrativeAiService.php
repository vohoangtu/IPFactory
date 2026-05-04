<?php

namespace App\Modules\Narrative\Services;

class NarrativeAiService
{
    public function generate(): string { return ""; }
    public function generateChronicle(int $universeId, int $fromTick, int $toTick, string $type): ?\App\Modules\Narrative\Models\Chronicle
    {
        return \App\Modules\Narrative\Models\Chronicle::where('universe_id', $universeId)->latest()->first();
    }
}

