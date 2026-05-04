<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Services\Dashboard;

use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Storage;

class WavefunctionProjector
{
    public function project(WorldState $state, int $universeId): array
    {
        $fields = $state->getFields();
        $pressures = $state->getPressures();
        $entropy = $state->getEntropy();
        $attractor = $state->getActiveAttractor();
        $stability = (float) $state->get('stability_index', 1.0);
        $density = (float) $state->get('meta.information_density', 0.0);
        $tick = (int) $state->get('tick', 0);

        $collapseProbability = max(0.0, min(1.0, $entropy * (1 - $stability) * (1 + $density)));

        return [
            'universe_id' => $universeId,
            'tick' => $tick,
            'wavefunction' => [
                'entropy' => $entropy,
                'stability_index' => $stability,
                'information_density' => $density,
                'active_attractor' => $attractor,
                'collapse_probability' => round($collapseProbability, 4),
                'fields' => $fields,
                'pressures' => $pressures,
            ],
            'causal_topology' => [
                'ancestor_ids' => (array) $state->get('meta.ancestor_universe_ids', []),
                'residual_seeds' => $state->get('meta.residual_seeds', []),
                'inherited_attractor' => $state->get('meta.inherited_attractor', 'none'),
            ],
            'autopoiesis' => [
                'enabled' => (bool) config('worldos.autopoiesis.enabled', true),
                'entropy_threshold' => (float) config('worldos.autopoiesis.entropy_threshold', 0.70),
                'mutation_history_size' => count(
                    Storage::disk('local')->directories('simulation/mutated_rules')
                ),
                'last_mutation_vector' => $state->get('meta.last_autopoiesis_vector', null),
            ],
        ];
    }
}
