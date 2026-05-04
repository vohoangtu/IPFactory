<?php

namespace App\Modules\Simulation\Services\Culture;

use App\Modules\World\Models\Universe;
use function resource_path;
use function file_get_contents;
use function abs;
use function min;
use function max;
use function round;
use function is_array;
use function json_decode;
use function is_string;

/**
 * Doc §10: Conversion probability (ideology A → B) from legitimacy and coherence.
 * Used by IdeologyEvolutionEngine to store conversion_rate_per_tick in state.
 */
final class IdeologyConversionService
{
    private const IDEOLOGY_KEYS = ['tradition', 'innovation', 'trust', 'violence', 'respect', 'myth'];

    public function __construct(
        protected \App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService $ruleVm
    ) {}

    use \App\Modules\Simulation\Core\Concerns\HasProductTypes; // Just placeholder if needed or just functions

    /**
     * Compute probability per tick that population/institutions shift toward target ideology.
     * Higher legitimacy_aggregate and cultural_coherence increase conversion rate.
     */
    public function conversionProbability(Universe $universe, array $fromIdeology, array $toIdeology): float
    {
        $snapshot = $universe->snapshots()->orderByDesc('tick')->first();
        if (!$snapshot) return 0.01;

        // Load Ideology DSL
        $dsl = @file_get_contents(resource_path('worldos_rules/ideology/conversion.dsl')) ?: '';
        
        // Execute via Rule VM
        $result = $this->ruleVm->evaluateRaw($universe, $snapshot, $dsl);
        
        if (!($result['ok'] ?? false)) return 0.01;

        $state = $result['state'] ?? [];
        $rate = (float) ($state['ideology']['conversion_rate'] ?? 0.01);
        
        // Tuy nhiên, logic distanceFactor trong PHP gốc khá phức tạp (loop qua IDEOLOGY_KEYS)
        // Ta giữ lại một phần logic PHP cho distanceFactor nếu DSL không tính được
        $distance = $this->calculateDistance($fromIdeology, $toIdeology);
        $distanceFactor = min(1.0, $distance * 2);

        return round(max(0.0, min(0.1, $rate * $distanceFactor)), 6);
    }

    private function calculateDistance(array $from, array $to): float
    {
        $distance = 0.0;
        $n = 0;
        foreach (self::IDEOLOGY_KEYS as $k) {
            $a = (float) ($from[$k] ?? 0.5);
            $b = (float) ($to[$k] ?? 0.5);
            $distance += abs($b - $a);
            $n++;
        }
        return $n > 0 ? $distance / $n : 0;
    }

    /**
     * Compute and return conversion rate from current dominant to previous (for drift tracking).
     */
    public function conversionRateToPrevious(Universe $universe): float
    {
        $state = $this->getStateVector($universe);
        $current = $state['dominant_ideology'] ?? null;
        $previous = $state['previous_dominant_ideology'] ?? null;
        if (! is_array($current) || ! is_array($previous)) {
            return 0.0;
        }
        return $this->conversionProbability($universe, $previous, $current);
    }

    private function getStateVector(Universe $universe): array
    {
        $sv = $universe->state_vector;
        if (is_string($sv)) {
            $sv = json_decode($sv, true) ?? [];
        }
        return is_array($sv) ? $sv : [];
    }
}



