<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Narrative\Dto\NarrativeMeaning;
use App\Modules\Narrative\Dto\NarrativeSignal;

/**
 * SignalBuilder: The "System Brain" that converts AI meaning into deterministic math.
 */
class SignalBuilder
{
    /**
     * Map narrative tension and direction to simulation deltas.
     */
    public function build(NarrativeMeaning $meaning): NarrativeSignal
    {
        $entropyDelta = $this->calculateEntropyDelta($meaning);
        $stabilityDelta = $this->calculateStabilityDelta($meaning);

        return new NarrativeSignal(
            entropyDelta: $entropyDelta,
            stabilityDelta: $stabilityDelta,
            metadata: [
                'tension_source' => $meaning->tension,
                'direction_source' => $meaning->direction
            ]
        );
    }

    protected function calculateEntropyDelta(NarrativeMeaning $meaning): float
    {
        return match ($meaning->tension) {
            'high'   => 0.02,
            'medium' => 0.005,
            'low'    => -0.005,
            default  => 0.0,
        };
    }

    protected function calculateStabilityDelta(NarrativeMeaning $meaning): float
    {
        $delta = match ($meaning->direction) {
            'collapse'   => -0.02,
            'stagnation' => -0.005,
            'growth'     => 0.015,
            default      => 0.0,
        };

        // Influence by key factors if necessary
        if (in_array('corruption', $meaning->keyFactors)) {
            $delta -= 0.01;
        }

        return $delta;
    }
}
