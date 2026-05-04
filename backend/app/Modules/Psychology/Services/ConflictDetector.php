<?php

namespace App\Modules\Psychology\Services;

use App\Modules\Psychology\ValueObjects\Conflict;
use App\Modules\Psychology\ValueObjects\Impulse;

/**
 * ConflictDetector – identifies which pairs of impulses are in opposition.
 *
 * Two impulses conflict if their actions are mutually opposing
 * (e.g. approach vs avoid, attack vs cooperate).
 * Returns Conflict[] to be processed by ConflictResolver.
 */
final class ConflictDetector
{
    /**
     * @param  Impulse[] $impulses
     * @return Conflict[]
     */
    public function detect(array $impulses): array
    {
        $conflicts = [];

        $count = count($impulses);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $impulses[$i];
                $b = $impulses[$j];

                if ($a->isOpposedTo($b)) {
                    $tension = $a->intensity * $b->intensity;
                    if ($tension > 0.01) {
                        $conflicts[] = new Conflict($a, $b, $tension);
                    }
                }
            }
        }

        return $conflicts;
    }
}
