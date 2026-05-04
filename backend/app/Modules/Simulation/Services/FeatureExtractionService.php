<?php

namespace App\Modules\Simulation\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;

/**
 * Doc §29: Extract numeric feature vector from universe/snapshot for AI policy-simulation.
 */
final class FeatureExtractionService
{
    /**
     * Extract a flat array of numeric features from universe and optional snapshot.
     *
     * @return array<string, float|int> Feature keys and values for policy-simulation input.
     */
    public function extract(Universe $universe, ?UniverseSnapshot $snapshot = null): array
    {
        $state = $this->getStateVector($universe);
        $metrics = $snapshot ? (array) ($snapshot->metrics ?? []) : [];
        $entropy = $snapshot ? (float) ($snapshot->entropy ?? 0.5) : (float) ($state['entropy'] ?? 0.5);
        $tick = $snapshot ? (int) $snapshot->tick : (int) ($universe->current_tick ?? 0);

        $economy = $state['civilization']['economy'] ?? [];
        $politics = $state['civilization']['politics'] ?? [];
        $demographic = $state['civilization']['demographic'] ?? [];
        $cognitive = $state['cognitive_aggregate'] ?? [];
        $war = $state['civilization']['war'] ?? [];
        $market = $state['economy']['market'] ?? [];
        $inequality = $economy['inequality'] ?? [];

        return [
            'entropy' => round($entropy, 4),
            'tick' => $tick,
            'stability_index' => round((float) ($state['stability_index'] ?? $state['sci'] ?? 0.5), 4),
            'total_surplus' => round((float) ($economy['total_surplus'] ?? 0), 4),
            'total_consumption' => round((float) ($economy['total_consumption'] ?? 0), 4),
            'trade_flow' => round((float) ($economy['trade_flow'] ?? 0), 4),
            'gini_index' => round((float) ($inequality['gini_index'] ?? 0), 4),
            'legitimacy_aggregate' => round((float) ($politics['legitimacy_aggregate'] ?? $politics['legitimacy'] ?? 0.5), 4),
            'elite_ratio' => round((float) ($politics['elite_ratio'] ?? 0), 4),
            'birth_rate' => round((float) ($demographic['birth_rate'] ?? 0.02), 4),
            'death_rate' => round((float) ($demographic['death_rate'] ?? 0.015), 4),
            'destiny_gradient' => round((float) ($cognitive['destiny_gradient'] ?? 0.5), 4),
            'causal_curiosity' => round((float) ($cognitive['causal_curiosity'] ?? 0.5), 4),
            'existential_tension' => round((float) ($cognitive['existential_tension'] ?? 0.5), 4),
            'military_power' => round((float) ($war['military_power'] ?? 0), 4),
            'conflict_pressure' => round((float) ($war['conflict_pressure'] ?? 0), 4),
            'price_food' => round((float) (($market['prices'] ?? [])['food'] ?? 1.0), 4),
            'volatility' => round((float) ($market['volatility'] ?? 0), 4),
            'energy_level' => round((float) ($metrics['energy_level'] ?? 0.5), 4),
            'order' => round((float) ($metrics['order'] ?? 0.5), 4),
        ];
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


