<?php

namespace App\Modules\Institutions\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Models\MaterialInstance;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use Illuminate\Support\Facades\Log;
use function resource_path;
use function config;
use function app;
use function mt_rand;
use function lcg_value;
use function max;
use function min;

class SocialDynamicsEngine
{
    public const DIMENSIONS = [
        'tradition',
        'innovation',
        'trust',
        'violence',
        'respect',
        'myth'
    ];

    public function __construct(
        protected \App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService $ruleVm,
        protected \App\Modules\Intelligence\Services\CultureEngine $cultureEngine
    ) {
        $this->cultureEngine = \app(\App\Modules\Intelligence\Services\CultureEngine::class);
    }

    /**
     * Advance collective cultural dynamics using the unified state manifold.
     */
    public function runWithState(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, int $tick): void
    {
        $entropy = (float) $state->get('entropy', 0.5);
        $stability = (float) $state->get('stability_index', 0.5);
        $resonance = (float) $state->get('resonance_field', 0.0);

        // 1. Evaluate Social Dynamics DSL
        $dslFile = resource_path('worldos_rules/simulation/social.dsl');
        if (!file_exists($dslFile)) {
            $dslFile = resource_path('worldos_rules/society/dynamics.dsl');
        }
        $dsl = @file_get_contents($dslFile) ?: '';

        $rawState = [
            'entropy' => $entropy,
            'stability' => $stability,
            'resonance_field' => $resonance,
            'era' => $state->get('era', 'genesis'),
            'fields' => $state->getFields()
        ];

        $result = $this->ruleVm->evaluateRawState($rawState, $dsl);
        $dslState = $result['state'] ?? [];

        $ethos = [
            'rigidity' => (float) ($dslState['rigidity'] ?? 0.5),
            'openness' => (float) ($dslState['openness'] ?? 0.5),
            'resilience' => (float) ($dslState['resilience'] ?? 0.5),
            'spirituality' => (float) ($dslState['spirituality'] ?? 0.5),
            'solidarity' => (float) ($dslState['solidarity'] ?? 0.5),
        ];

        // 2. Symbolic/Institutional material pressure (Mocked for manifold for now)
        // In a real scenario, these would be pre-loaded into State
        
        $state->set('ethos', $ethos);
        
        // 3. Culture Diffusion in Zones
        $this->applyCultureDiffusionManifold($state, $dslState);
    }

    public function advance(Universe $universe, int $tick): array
    {
        // Deprecated: Pipeline handles runWithState
        return [];
    }

    public function calculateUniverseEthos(Universe $universe, array &$metadata = []): array
    {
        $latest = $universe->snapshots()->orderByDesc('tick')->first();
        $entropy = (float)($latest->entropy ?? 0.5);
        $stability = (float)($latest->stability_index ?? 0.5);

        $dslFile = resource_path('worldos_rules/society/dynamics.dsl');
        $dsl = @file_get_contents($dslFile) ?: '';

        $rawState = [
            'entropy' => $entropy,
            'stability' => $stability,
        ];

        $result = $this->ruleVm->evaluateRawState($rawState, $dsl);
        $dslState = $result['state'] ?? [];
        $metadata = $result['state'] ?? []; // Metadata comes from state in evaluateRawState

        $ethos = [
            'rigidity' => (float) ($dslState['rigidity'] ?? 0.5),
            'openness' => (float) ($dslState['openness'] ?? 0.5),
            'resilience' => (float) ($dslState['resilience'] ?? 0.5),
            'spirituality' => (float) ($dslState['spirituality'] ?? 0.5),
            'solidarity' => (float) ($dslState['solidarity'] ?? 0.5),
        ];

        $activeMaterials = MaterialInstance::where('universe_id', $universe->id)
            ->whereHas('material', function($query) {
                $query->where('ontology', \App\Modules\World\Models\Material::ONTOLOGY_SYMBOLIC)
                      ->orWhere('ontology', \App\Modules\World\Models\Material::ONTOLOGY_INSTITUTIONAL);
            })
            ->with('material')
            ->get();

        foreach ($activeMaterials as $instance) {
            $coefficients = $instance->material->pressure_coefficients ?? [];
            foreach ($coefficients as $key => $value) {
                if (isset($ethos[$key])) {
                    $ethos[$key] = max(0, min(1, $ethos[$key] + ($value * $instance->current_value / 10)));
                }
            }
        }

        return $ethos;
    }

    protected function applyCultureDiffusionManifold(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, array $metadata = []): void
    {
        $zones = $state->get('zones', []);
        if (empty($zones) || !is_array($zones)) return;

        $epsilon = (float) ($metadata['drift_epsilon'] ?? 0.001);
        $beta = (float) ($metadata['diffusion_beta'] ?? 0.005);

        // 1. Drift
        foreach ($zones as &$zone) {
            $culture = $zone['culture'] ?? $this->initialCulture();
            foreach (self::DIMENSIONS as $dim) {
                $rngSeed = (int)$state->get('seed', 0) + (int)$state->get('universe_id', 0);
                $drift = (($rngSeed % 201 - 100) / 1000.0) * $epsilon; // Det. pseudo-random
                $culture[$dim] = max(0.0, min(1.0, ($culture[$dim] ?? 0.5) + $drift));
            }
            $zone['culture'] = $culture;
        }

        // 2. Diffusion (Ring Topology)
        $newZones = $zones;
        $count = count($zones);
        if ($count > 1) {
            foreach ($zones as $i => $zone) {
                $neighbors = [($i - 1 + $count) % $count, ($i + 1) % $count];
                foreach ($neighbors as $nIdx) {
                    foreach (self::DIMENSIONS as $dim) {
                        $diff = ($zones[$nIdx]['culture'][$dim] - $zone['culture'][$dim]) * $beta;
                        $newZones[$i]['culture'][$dim] = max(0.0, min(1.0, $newZones[$i]['culture'][$dim] + $diff));
                    }
                }
            }
        }

        $state->set('zones', $newZones);
    }
    
    protected function applyCultureDiffusion(Universe $universe, array $metadata = []): void
    {
        // Deprecated
    }

    protected function initialCulture(): array
    {
        return [
            'tradition' => 0.5,
            'innovation' => 0.1,
            'trust' => 0.7,
            'violence' => 0.1,
            'respect' => 0.6,
            'myth' => 0.8,
        ];
    }
}






