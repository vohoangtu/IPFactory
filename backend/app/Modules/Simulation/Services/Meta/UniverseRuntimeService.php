<?php

namespace App\Modules\Simulation\Services\Meta;

use App\Contracts\SimulationEngineClientInterface;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\BranchEvent;
use App\Modules\Simulation\Repositories\UniverseSnapshotRepository;
use App\Modules\Simulation\Services\CultureDiffusionService;
use App\Modules\Simulation\Core\Engines\Meta\DecisionEngine;
use App\Modules\Simulation\Services\InstitutionalEngine;

class UniverseRuntimeService
{
    public function __construct(
        protected SimulationEngineClientInterface $engine,
        protected UniverseSnapshotRepository $snapshots,
        protected ?NarrativeAiService $narrativeAi = null,
        protected ?CultureDiffusionService $cultureDiffusion = null,
        protected ?InstitutionalEngine $institutionalEngine = null,
        protected ?MultiverseInteractionService $multiverseInteraction = null,
        protected ?AutonomyEngine $autonomyEngine = null,
        protected ?\App\Modules\Intelligence\Actions\SocialContractEvolutionAction $evolutionAction = null,
        protected ?GreatFilterEngine $greatFilter = null,
        protected ?WorldWillEngine $worldWill = null,
        protected ?\App\Modules\Narrative\Actions\AscensionAction $ascensionAction = null,
        protected ?\App\Modules\Narrative\Actions\CelestialEngineeringAction $celestialEngineering = null,
        protected ?ConvergenceEngine $convergenceEngine = null
    ) {}

    /**
     * Advance universe by N ticks. Delegating to the new refactored Action.
     */
    public function advance(int $universeId, int $ticks): array
    {
        return app(\App\Modules\Simulation\Actions\AdvanceSimulationAction::class)->execute($universeId, $ticks);
    }

    /**
     * Build context array for Material Lifecycle (entropy, order, innovation, etc. from snapshot).
     */
    protected function buildMaterialContext(array $snapshot, array $stateVector, array $metrics): array
    {
        $entropy = $snapshot['entropy'] ?? 0;
        $stability = $snapshot['stability_index'] ?? 0;
        
        // Extract scars from state vector
        $scars = [];
        if (isset($stateVector['scars']) && is_array($stateVector['scars'])) {
            $scars = $stateVector['scars'];
        }

        // Count ontology types for resonance
        // This would require fetching material instances, but for now we pass empty or simplified
        // Ideally MaterialLifecycleEngine should handle aggregation, but we can pass hints here.

        return array_merge($metrics, [
            'entropy' => is_numeric($entropy) ? (float) $entropy : 0,
            'order' => is_numeric($stability) ? (float) $stability : 0,
            'innovation' => $metrics['innovation'] ?? 0,
            'growth' => $metrics['growth'] ?? 0,
            'trauma' => $metrics['trauma'] ?? 0,
            'scars' => $scars,
        ]);
    }
}





