<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Narrative\Models\Myth;

class ReligionSeedDetector
{
    public function isReligionSeed($myth): bool
    {
        if (!$myth instanceof Myth) {
            return false;
        }

        $impactThreshold = (float) config('worldos.narrative.religion_impact_threshold', 0.6);
        $impact = (float) ($myth->impact ?? 0);
        $mythType = strtolower((string) ($myth->myth_type ?? ''));
        $story = mb_strtolower((string) ($myth->story ?? ''));

        if ($impact >= $impactThreshold) {
            return true;
        }

        if (in_array($mythType, ['religion', 'covenant', 'origin', 'martyr', 'creator', 'oikos'], true)) {
            return true;
        }

        foreach (['sacred', 'holy', 'divine', 'ancestor', 'prophecy', 'covenant', 'revelation', 'ritual', 'faith'] as $keyword) {
            if (str_contains($story, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
