<?php

namespace App\Modules\Simulation\Core\Engines\Physics;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

/**
 * Metabolic Engine 🍎⚡
 * 
 * Manages the energy accounting for the universe.
 * Energy = Growth - Decay - Entropy.
 */
class MetabolicEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string
    {
        return 'metabolic_engine';
    }

    public function phase(): string
    {
        return 'physics';
    }

    public function priority(): int
    {
        return 11;
    }

    public function tickRate(): int
    {
        return 1;
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $universeId = $ctx->getUniverseId();
        $universe = Universe::find($universeId);
        
        if (!$universe || !$universe->axioms) {
            return new EngineResult([], [], []);
        }

        $axioms = $universe->axioms;
        $efficiency = (float)($axioms['energy_efficiency'] ?? 0.4);
        $entropyDecay = (float)($axioms['entropy_decay_rate'] ?? 0.05);
        $baseEnergy = 1.0; 

        $zones = $state->get('zones', []);
        
        $populations = [];
        $biomasses = [];
        $industries = [];
        $isGlobal = false;

        $vec = $state->getStateVector();

        if (empty($zones)) {
            $populations[] = (float) ($vec['population'] ?? 1000);
            $biomasses[] = (float) ($vec['biomass'] ?? $populations[0] * 0.1); 
            $industries[] = (float) ($vec['industrial_activity'] ?? 0);
            $isGlobal = true;
        } else {
            foreach ($zones as $zone) {
                $populations[] = (float) ($zone['state']['population'] ?? 0);
                $biomasses[] = (float) ($zone['state']['biomass'] ?? 0);
                $industries[] = (float) ($zone['state']['industrial_activity'] ?? 0);
            }
        }

        // FFI Call
        $ffiEngine = app(\App\Modules\Simulation\Services\RuleEngine\FfiRuleEngine::class);
        $result = $ffiEngine->computeMetabolismGrid($populations, $biomasses, $industries, $efficiency, $baseEnergy);

        $totalWaste = $result['total_waste'] ?? 0.0;
        $netEnergies = $result['net_energies'] ?? [];

        // Apply back to State
        $totalNetEnergy = 0.0;
        if ($isGlobal) {
            $state->set('fields.population', max(0.0, $populations[0]));
            $state->set('fields.biomass', max(0.0, $biomasses[0]));
            $totalNetEnergy = $netEnergies[0] ?? 0.0;
        } else {
            foreach ($zones as $i => &$zone) {
                $zone['state']['population'] = max(0.0, $populations[$i]);
                $zone['state']['biomass'] = max(0.0, $biomasses[$i]);
                $zone['state']['net_energy'] = $netEnergies[$i];
                $totalNetEnergy += $netEnergies[$i];
            }
            $state->set('zones', $zones);
        }

        // Global Entropy Math
        $currentEntropy = (float) $state->get('entropy', 0.5);
        $nextEntropy = max(0.0, min(1.0, $currentEntropy + $totalWaste - $entropyDecay));
        $state->set('entropy', $nextEntropy);

        // Detect Starvation
        $avgNetEnergy = $totalNetEnergy / max(1, count($netEnergies));
        if ($avgNetEnergy < -0.5) {
            $state->set('survival_modifier', 0.7);
            Log::warning("METABOLIC DEFICIT in Universe [{$universeId}]: Population is starving.");
        } else {
            $state->set('survival_modifier', 1.0);
        }

        return new EngineResult([
            'avg_net_energy' => $avgNetEnergy,
            'total_waste' => $totalWaste,
            'entropy' => $nextEntropy,
            'survival_modifier' => ($avgNetEnergy < -0.5) ? 0.7 : 1.0,
            'zones' => $isGlobal ? null : $zones,
            'fields.population' => $isGlobal ? max(0.0, $populations[0]) : null,
            'fields.biomass' => $isGlobal ? max(0.0, $biomasses[0]) : null,
        ], [], []);
    }
}

