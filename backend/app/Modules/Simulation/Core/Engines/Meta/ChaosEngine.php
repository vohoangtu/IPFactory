<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Models\Chronicle;
use Illuminate\Support\Facades\Log;

/**
 * ChaosEngine: Unbridled Probability Manager (§V25) + Stability & Chaos Control (Doc §32).
 * Introduces core logic destabilization for 'chaotic' worlds.
 * Four mechanisms: dampening, event throttling, biological feedback (external), chaos quarantine.
 */
class ChaosEngine
{
    /** Dampening: multiply raw rate by stability factor (0..1) to reduce volatility. */
    public function dampen(float $rawRate, float $stabilityFactor): float
    {
        $factor = max(0.0, min(1.0, (float) config('worldos.chaos.dampening_stability_factor', 0.6)));
        return $rawRate * ($stabilityFactor * (1.0 - $factor) + $factor);
    }

    /** Event throttling: reduce probability when count > threshold (e.g. war_probability *= 0.5). */
    public function throttleProbability(float $probability, int $eventCount, int $threshold): float
    {
        if ($eventCount <= $threshold) {
            return $probability;
        }
        $multiplier = (float) config('worldos.chaos.throttle_multiplier', 0.5);
        return $probability * $multiplier;
    }

    /** Chaos quarantine: return influence scale 0..1 for a zone (0 = do not propagate to neighbors). */
    public function quarantineInfluence(float $zoneInstability): float
    {
        $threshold = (float) config('worldos.chaos.quarantine_instability_threshold', 0.8);
        if ($zoneInstability >= $threshold) {
            return (float) config('worldos.chaos.quarantine_scale', 0.2);
        }
        return 1.0;
    }

    /**
    /**
     * Determine if a chaotic override should happen this tick.
     */
    public function destabilize(Universe $universe): void
    {
        $world = $universe->world;

        // Only apply to chaotic worlds
        if (!$world || !$world->is_chaotic) {
            return;
        }

        $prng = \App\Modules\Simulation\Services\Ecology\SimulationPRNG::forUniverse($universe);

        // Rare full anomaly
        if ($prng->nextInt(0, 10000) > 1) {
            return;
        }

        $this->triggerParadox($universe);
    }

    protected function triggerParadox(Universe $universe): void
    {
        // Pick a random paradox
        $paradoxes = ['entropy_inversion', 'sci_singularity', 'zone_collapse',
            'time_loop',
        ];

        $prng = clone \App\Modules\Simulation\Services\Ecology\SimulationPRNG::forUniverse($universe);
        $paradox = $prng->randomElement($paradoxes);

        switch ($paradox) {
            case 'entropy_inversion':
                // Swap SCI and Entropy
                $temp = $universe->structural_coherence;
                $universe->structural_coherence = $universe->entropy;
                $universe->entropy = $temp;
                break;
            
            case 'sci_singularity':
                // Temporarily force SCI to impossible levels
                $universe->structural_coherence = 1.5; // Breaking the 1.0 boundary
                break;

            case 'zone_collapse':
                // Wipe radiation from all zones unpredictably
                $vec = $universe->state_vector;
                if (isset($vec['zones'])) {
                    foreach ($vec['zones'] as &$zone) {
                        $zone['state']['radiation'] = 0;
                        $zone['state']['vitality'] = 1.0;
                    }
                    $universe->state_vector = $vec;
                }
                break;
        }

        $universe->save();

        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $universe->current_tick,
            'to_tick' => $universe->current_tick,
            'type' => 'chaos_anomaly',
            'raw_payload' => [
                'action' => 'paradox_triggered',
                'paradox_type' => $paradox,
                'impact' => 'massive_reality_warp'
            ],
        ]);

        Log::warning("CHAOS MATRIX: Reality fractured in Universe #{$universe->id}. Type: {$paradox}");
    }
}


