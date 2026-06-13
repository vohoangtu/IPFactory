<?php

namespace App\Modules\Simulation\Core\Runtime\Stages;

use App\Modules\Simulation\Core\Runtime\State\StateManager;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use function resource_path;
use function file_get_contents;
use function config;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Runtime\Contracts\SimulationStageInterface;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use App\Modules\Simulation\Core\Runtime\RuleVM\DslPayload;
use App\Modules\Simulation\Services\Culture\ResonanceAuditorService;
use App\Modules\Simulation\Services\Meta\MultiverseSovereigntyService;
use App\Modules\Narrative\Actions\ArchetypeShiftAction;
use App\Modules\Intelligence\Services\AI\FaithService;
use App\Modules\Narrative\Actions\EmpowerDemiurgesAction;
use App\Modules\Narrative\Actions\DemiurgeAutonomousAction;
use App\Modules\Narrative\Actions\DivineMiracleAction;
use App\Modules\Narrative\Services\EtherealOmenService;
use App\Modules\Simulation\Services\Cosmology\HeatDeathService;
use App\Modules\Simulation\Actions\AutonomousAxiomMutationAction;
use App\Modules\Intelligence\Actions\AgentSovereigntyAction;
use App\Modules\Simulation\Core\Engines\Biological\CelestialAntibodyEngine;
use App\Modules\Simulation\Core\Engines\Meta\ChaosEngine;
use App\Modules\Simulation\Core\Engines\Meta\TransmigrationEngine;
use App\Modules\Simulation\Core\Engines\Meta\InformationDensityEngine;
use App\Modules\Simulation\Core\Engines\Biological\AutopoieticEvolutionEngine;
use App\Modules\Narrative\Services\NarrativeChapterEngine;
use App\Modules\Narrative\Services\MeaningLoopService;
use App\Modules\Simulation\Core\Engines\Meta\MultiverseEconomyEngine;

/**
 * Meta / Cosmic layer: resonance, sovereignty, archetype, alignments, demiurges,
 * miracles, heat death, axiom mutation, agent sovereignty, antibody, chaos, transmigration.
 */
final class MetaCosmicStage implements SimulationStageInterface
{
    public function __construct(
        protected ResonanceAuditorService $resonanceAuditor,
        protected MultiverseSovereigntyService $sovereignty,
        protected ArchetypeShiftAction $archetypeShift,
        protected FaithService $faithService,
        protected EmpowerDemiurgesAction $empowerDemiurges,
        protected DemiurgeAutonomousAction $demiurgeAction,
        protected DivineMiracleAction $miracleAction,
        protected EtherealOmenService $etherOmen,
        protected HeatDeathService $heatDeath,
        protected AutonomousAxiomMutationAction $axiomMutation,
        protected AgentSovereigntyAction $agentSovereignty,
        protected CelestialAntibodyEngine $antibodyEngine,
        protected ChaosEngine $chaosEngine,
        protected TransmigrationEngine $transmigrationEngine,
        protected \App\Modules\Intelligence\Domain\Society\PolarizationCalculator $polarizationCalculator,
        protected \App\Modules\Intelligence\Services\MacroStateEvolution $macroEvolution,
        protected \App\Modules\Intelligence\Services\SocietyAnalyzer $societyAnalyzer,
        protected \App\Modules\Intelligence\Contracts\ActorRepositoryInterface $actorRepository,
        protected \App\Modules\Intelligence\Services\FactionAIEngine $factionAIEngine,
        protected \App\Modules\Intelligence\Services\SocietyMetricsCalculator $societyMetricsCalculator,
        protected \App\Modules\Intelligence\Services\DiplomaticEngine $diplomaticEngine,
        protected \App\Modules\Intelligence\Services\ZoneFieldCalculator $zoneFieldCalculator,
        protected \App\Modules\Intelligence\Services\FieldDiffusionEngine $fieldDiffusionEngine,
        protected \App\Modules\Intelligence\Services\UniverseFitnessEvaluator $universeFitnessEvaluator,
        protected \App\Modules\Intelligence\Actions\UniverseMutationAction $universeMutationAction,
        protected \App\Modules\Intelligence\Actions\UniverseForkAction $universeForkAction,
        protected \App\Modules\Intelligence\Services\CivilizationCollapseEngine $civilizationCollapseEngine,
        protected \App\Modules\Intelligence\Services\GenomeTransitionService $genomeTransitionService,
        protected \App\Modules\Intelligence\Services\GenomeAdaptationService $genomeAdaptationService,
        protected \App\Modules\Intelligence\Services\InformationLayerService $informationLayerService,
        protected \App\Modules\Intelligence\Services\InstitutionManager $institutionManager,
        protected \App\Modules\Intelligence\Services\GreatPersonEngine $greatPersonEngine,
        protected \App\Modules\Intelligence\Services\LegacySystem $legacySystem,
        protected \App\Modules\Intelligence\Services\CatalystEngine $catalystEngine,
        protected \App\Modules\Intelligence\Services\InstitutionEngine $institutionEngine,
        protected \App\Modules\Intelligence\Services\InnovationEngine $innovationEngine,
        protected \App\Modules\Intelligence\Services\PolityCompetitionEngine $polityEngine,
        protected \App\Modules\Intelligence\Services\IdeaDiffusionEngine $ideaDiffusionEngine,
        protected \App\Modules\Intelligence\Services\MacroAgentEngine $macroAgentEngine,
        protected \App\Modules\Intelligence\Services\DynastyEngine $dynastyEngine,
        protected \App\Modules\Simulation\Core\Runtime\Core\CosmicV2Orchestrator $cosmicV2Orchestrator,
        protected \App\Modules\Simulation\Core\Runtime\State\StateManager $stateManager,
        protected RuleVmService $ruleVm,
        protected \App\Modules\Simulation\Services\Ecology\AnomalyGeneratorService $anomalyGenerator,
        protected InformationDensityEngine $informationDensityEngine,
        protected AutopoieticEvolutionEngine $autopoieticEngine,
        protected NarrativeChapterEngine $narrativeChapterEngine,
        protected MeaningLoopService $meaningLoopService,
        protected MultiverseEconomyEngine $multiverseEconomyEngine
    ) {}

    public function run(Universe $universe, int $tick, ?UniverseSnapshot $savedSnapshot = null, array $context = []): void
    {
        if ($universe->status === 'collapsed') return;

        $state = $this->stateManager->get();
        if (!$state) return;

        // 1. Audit Resonance via Manifold
        $this->resonanceAuditor->runWithState($state, $tick);

        // 2. Anomalies & Disasters via Manifold
        $this->anomalyGenerator->runWithState($state, $tick);

        $ctx = new \App\Modules\Simulation\Core\Domain\TickContext((int) ($universe->id ?? 0), $tick, (int) ($universe->seed ?? 0));

        // 2.1 Information Density & Terminal Horizon
        $this->informationDensityEngine->handle($state, $ctx);

        // 2.2 Autopoietic Evolution (Self-Modifying Logic)
        $this->autopoieticEngine->handle($state, $ctx);

        // 2.3 V10+ Vector loop closures
        $this->meaningLoopService->runWithState($state, $tick);
        $this->narrativeChapterEngine->runWithState($state, $tick);
        $this->multiverseEconomyEngine->handle($state, $ctx);

        // 3. Top-level Cosmic DSL (Heat Death, Sovereignty, Omens)
        $cosmicDslFile = resource_path('worldos_rules/simulation/cosmic.dsl');
        if (file_exists($cosmicDslFile)) {
            $this->ruleVm->evaluateAndApplyWithDsl($state, file_get_contents($cosmicDslFile), $tick);
        }

        // 4. Divine Miracles (Random)
        if ($tick % 5 === 0) {
            $this->triggerManifoldMiracles($state, $tick);
        }

        // Legacy / Mixed Systems (To be fully unified in next audit)
        $this->agentSovereignty->execute($universe);
        $this->antibodyEngine->execute($universe);
        
        // 5. Civilization Collapse (V10)
        $this->civilizationCollapseEngine->evaluate($universe, $savedSnapshot ?? new UniverseSnapshot(['tick' => $tick]));
        
        // Finalize V2 Orchestration
        if ($savedSnapshot) {
            $this->cosmicV2Orchestrator->run($universe, $savedSnapshot);
        }
    }

    private function triggerManifoldMiracles(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, int $tick): void
    {
        $rivals = \App\Modules\World\Models\Demiurge::where('is_active', true)->get();
        $rng = new \App\Support\Simulation\SimulationPRNG($state->get('seed', 0) + $tick);
        
        foreach ($rivals as $demiurge) {
            $chance = ($demiurge->will_power / 5000) + (float)($state->get('meta.omen.sci_modifier', 0));
            if ($rng->nextFloat() < $chance && $chance > 0) {
                $types = ['absolute_order', 'void_eruption', 'legendary_ascension'];
                $this->miracleAction->executeWithState($demiurge, $state, $rng->randomElement($types), $tick);
            }
        }
    }

    private function calculateArchetypeRatios(array $actors): array
    {
        if (empty($actors)) return [];
        $counts = [];
        foreach ($actors as $actor) {
            $counts[$actor->archetype] = ($counts[$actor->archetype] ?? 0) + 1;
        }
        $total = count($actors);
        $ratios = [];
        foreach ($counts as $arch => $count) {
            $ratios[$arch] = $count / $total;
        }
        return $ratios;
    }

    private function processAlignments(Universe $universe, \App\Support\Simulation\SimulationPRNG $rng): void
    {
        $legends = \App\Modules\Intelligence\Models\LegendaryAgent::where('universe_id', $universe->id)->get();
        foreach ($legends as $legend) {
            $favored = is_array($legend->fate_tags) && in_array('divine_favor', $legend->fate_tags);
            $growthMod = $favored ? 1.5 : 1.0;
            
            $traits = [
                'order' => $rng->nextFloat() * $growthMod,
                'entropy' => $rng->nextFloat() * (1.0 / $growthMod),
            ];
            $this->faithService->updateAlignment($legend, $traits);
        }
    }

    private function triggerRandomMiracles(Universe $universe, \App\Support\Simulation\SimulationPRNG $rng): void
    {
        $rivals = \App\Modules\World\Models\Demiurge::where('is_active', true)->get();
        $omen = $this->etherOmen->generateInternalOmen($universe);
        foreach ($rivals as $demiurge) {
            $chance = ($demiurge->will_power / 2000) + ($omen['sci_impact'] ?? 0);
            if ($rng->nextFloat() < $chance && $chance > 0) {
                $types = ['absolute_order', 'void_eruption', 'legendary_ascension'];
                $this->miracleAction->execute($demiurge, $universe, $rng->randomElement($types));
            }
        }
    }

    private function spawnHeir(\App\Modules\World\Models\Universe $universe, \App\Modules\Intelligence\Entities\ActorState $parentState): void
    {
        // Simple logic for personification: spawn one child for the dead hero
        $childId = 1000 + $parentState->id; // Mock/Temporal ID
        $child = new \App\Modules\Intelligence\Entities\ActorEntity(
            id: $childId,
            universeId: $universe->id,
            name: "Heir of " . $parentState->name,
            archetype: $parentState->archetype,
            traits: [], // Will be inherited
            metrics: [],
            isAlive: true,
            generation: ($parentState->generation ?? 1) + 1
        );

        // Convert state to entity for inherit method
        $parentEntity = new \App\Modules\Intelligence\Entities\ActorEntity(
            id: $parentState->id,
            universeId: $parentState->universeId,
            name: $parentState->name,
            archetype: $parentState->archetype,
            traits: $parentState->traits,
            metrics: $parentState->metrics,
            isAlive: $parentState->isAlive,
            generation: $parentState->generation,
            biography: $parentState->biography,
            isHeroic: $parentState->isHeroic,
            heroicType: $parentState->heroicType
        );

        $this->dynastyEngine->inherit($child, $parentEntity);
        
        // Save to repository
        $this->actorRepository->save($child);
        \Illuminate\Support\Facades\Log::info("DYNASTY EMERGENCE: {$child->name} has risen to continue the legacy of {$parentState->name}");
    }
}




