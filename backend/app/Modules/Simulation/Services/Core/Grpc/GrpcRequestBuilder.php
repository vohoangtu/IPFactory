<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Services\Core\Grpc;

use Worldos\Simulation\AdvanceRequest;
use Worldos\Simulation\BatchAdvanceRequest;
use Worldos\Simulation\CalculateVocationAlignmentRequest;
use Worldos\Simulation\ComputeMetabolismGridRequest;
use Worldos\Simulation\EvaluateRulesRequest;
use Worldos\Simulation\GetCombinedGravityRequest;
use Worldos\Simulation\KernelGenome;
use Worldos\Simulation\MergeRequest;
use Worldos\Simulation\ProcessActorsSoaRequest;
use Worldos\Simulation\ProcessFieldsV7Request;
use Worldos\Simulation\TrajectoryAnalysisRequest;
use Worldos\Simulation\TrajectoryPoint;
use Worldos\Simulation\WorldConfig;

/**
 * Builds protobuf request messages for gRPC calls to the WorldOS simulation engine.
 * Extracted from GrpcSimulationEngineClient.
 */
class GrpcRequestBuilder
{
    public function buildAdvanceRequest(int $universeId, int $ticks, array $stateInput = [], ?array $worldConfig = null): AdvanceRequest
    {
        $request = new AdvanceRequest();
        $request->setUniverseId($universeId);
        $request->setTicks($ticks);
        $request->setStateInput(json_encode($stateInput));

        if ($worldConfig) {
            $config = new WorldConfig();
            $config->setWorldId($worldConfig['world_id'] ?? 0);
            $config->setOrigin($worldConfig['origin'] ?? '');
            $config->setAxiomJson(json_encode($worldConfig['axioms'] ?? []));

            if (isset($worldConfig['genome'])) {
                $genome = new KernelGenome();
                $genome->setDiffusionRate($worldConfig['genome']['diffusion_rate'] ?? 0.1);
                $genome->setEntropyCoefficient($worldConfig['genome']['entropy_coefficient'] ?? 1.0);
                $genome->setMutationRate($worldConfig['genome']['mutation_rate'] ?? 0.01);
                $genome->setAttractorGravity($worldConfig['genome']['attractor_gravity'] ?? 1.0);
                $genome->setComplexityBonus($worldConfig['genome']['complexity_bonus'] ?? 0.5);
                $config->setGenome($genome);
            }
            $request->setWorldConfig($config);
        }

        return $request;
    }

    public function buildMergeRequest(string $stateA, string $stateB): MergeRequest
    {
        $request = new MergeRequest();
        $request->setStateA($stateA);
        $request->setStateB($stateB);

        return $request;
    }

    public function buildBatchAdvanceRequest(array $requests): BatchAdvanceRequest
    {
        $batchRequest = new BatchAdvanceRequest();
        $protoRequests = [];
        foreach ($requests as $req) {
            $inner = new AdvanceRequest();
            $inner->setUniverseId($req['universe_id']);
            $inner->setTicks($req['ticks']);
            $inner->setStateInput(json_encode($req['state_input'] ?? []));
            $protoRequests[] = $inner;
        }
        $batchRequest->setRequests($protoRequests);

        return $batchRequest;
    }

    public function buildTrajectoryAnalysisRequest(array $points, float $threshold = 0.1): TrajectoryAnalysisRequest
    {
        $request = new TrajectoryAnalysisRequest();
        $protoPoints = [];
        foreach ($points as $p) {
            $tp = new TrajectoryPoint();
            $tp->setTick($p['tick']);
            $tp->setState($p['state']);
            $protoPoints[] = $tp;
        }
        $request->setPoints($protoPoints);
        $request->setRecurrenceThreshold($threshold);

        return $request;
    }

    public function buildEvaluateRulesRequest(array $state, ?string $rulesDsl = null): EvaluateRulesRequest
    {
        $request = new EvaluateRulesRequest();
        $request->setStateJson(json_encode($state));
        $request->setRulesDsl($rulesDsl ?? '');

        return $request;
    }

    public function buildProcessActorsSoaRequest(
        int $tick,
        array $ids,
        array $zoneIds,
        array $hunger,
        array $energy,
        array $fear,
        array $trauma,
        array $heroicTypes,
        array $lineageIds,
        array $memes,
        array $traitsMatrix,
        array $behaviorStates = [],
        array $behaviorGraphs = [],
        array $archetypes = [],
        array $socialGraph = [],
        array $edicts = [],
        array $factionIds = [],
        array $factionLoyalty = [],
        bool $isObserved = false,
        array $narrativeContext = [],
        array $factionRelations = [],
        array $beliefDefinitions = [],
        array $beliefAlignments = [],
        array $techDefinitions = [],
        array $actorTechLevels = []
    ): ProcessActorsSoaRequest {
        $request = new \Worldos\Simulation\ProcessActorsSoaRequest();
        $request->setTick($tick);
        $request->setIds($ids);
        $request->setZoneIds($zoneIds);
        $request->setHunger($hunger);
        $request->setEnergy($energy);
        $request->setFear($fear);
        $request->setTrauma($trauma);
        $request->setHeroicTypes($heroicTypes);
        $request->setLineageIds($lineageIds);
        $request->setMemes($memes);
        if (method_exists($request, 'setTraitsMatrix')) {
            $request->setTraitsMatrix($traitsMatrix);
        }
        if (method_exists($request, 'setBehaviorStates')) {
            $request->setBehaviorStates($behaviorStates);
        }
        if (method_exists($request, 'setBehaviorGraphs')) {
            // Note: behaviorGraphs is an array of BehaviorGraph objects
            $request->setBehaviorGraphs($behaviorGraphs);
        }
        if (method_exists($request, 'setArchetypes')) {
            $request->setArchetypes($archetypes);
        }

        // Phase 3: Social & Edicts
        if (!empty($socialGraph) && method_exists($request, 'setSocialGraph')) {
            $edges = [];
            foreach ($socialGraph as $edgeData) {
                $edge = new \Worldos\Simulation\SocialEdge();
                $edge->setSourceId($edgeData['source_id']);
                $edge->setTargetId($edgeData['target_id']);
                $edge->setWeight($edgeData['weight'] ?? 1.0);
                $edges[] = $edge;
            }
            $request->setSocialGraph($edges);
        }

        if (!empty($edicts) && method_exists($request, 'setEdicts')) {
            $protoEdicts = [];
            foreach ($edicts as $edictData) {
                $edict = new \Worldos\Simulation\Edict();
                $edict->setName($edictData['name']);
                $edict->setModifierType($edictData['modifier_type']);
                $edict->setValue($edictData['value']);
                $protoEdicts[] = $edict;
            }
            $request->setEdicts($protoEdicts);
        }

        if (!empty($narrativeContext) && method_exists($request, 'setActiveSagas')) {
            $protoSagas = [];
            foreach ($narrativeContext as $sagaData) {
                $saga = new \Worldos\Simulation\WorldSaga();
                $saga->setId($sagaData['id']);
                $saga->setName($sagaData['name']);
                $saga->setTheme($sagaData['theme']);

                $legends = [];
                foreach ($sagaData['legends'] ?? [] as $leg) {
                    $legend = new \Worldos\Simulation\WorldLegend();
                    $legend->setId($leg['id']);
                    $legend->setCategory($leg['category'] ?? '');
                    $legend->setTitle($leg['title']);
                    $legend->setDescription($leg['description']);
                    $legend->setTickStart($leg['tick_start']);
                    $legend->setTickEnd($leg['tick_end']);
                    $legend->setImportance($leg['importance']);
                    $legend->setInvolvedActorIds($leg['involved_actor_ids'] ?? []);
                    $legends[] = $legend;
                }
                $saga->setLegends($legends);
                $protoSagas[] = $saga;
            }
            $request->setActiveSagas($protoSagas);
        }

        if (method_exists($request, 'setFactionIds')) {
            $request->setFactionIds($factionIds);
        }
        if (method_exists($request, 'setFactionLoyalty')) {
            $request->setFactionLoyalty($factionLoyalty);
        }
        if (method_exists($request, 'setIsObserved')) {
            $request->setIsObserved($isObserved);
        }

        $protos = [];
        foreach ($factionRelations as $rel) {
            $p = new \Worldos\Simulation\FactionRelation();
            $p->setFactionA($rel['faction_a']);
            $p->setFactionB($rel['faction_b']);
            $p->setTension($rel['tension']);
            $protos[] = $p;
        }
        if (method_exists($request, 'setFactionRelations')) {
            $request->setFactionRelations($protos);
        }

        $beliefDefs = [];
        foreach ($beliefDefinitions as $def) {
            $p = new \Worldos\Simulation\BeliefDefinition();
            $p->setId($def['id']);
            $p->setName($def['name']);
            $p->setType($def['type']);
            $p->setTraitWeights($def['trait_weights']);
            $beliefDefs[] = $p;
        }
        if (method_exists($request, 'setBeliefDefinitions')) {
            $request->setBeliefDefinitions($beliefDefs);
        }

        if (method_exists($request, 'setBeliefAlignments')) {
            $request->setBeliefAlignments($beliefAlignments);
        }

        // Phase 14: Technology
        $techProtos = [];
        foreach ($techDefinitions as $tech) {
            $t = new \Worldos\Simulation\TechnologyDefinition();
            $t->setId((string)$tech['id']);
            $t->setName($tech['name']);
            $t->setCode($tech['code']);
            $t->setRequirements($tech['requirements'] ?? []);
            $t->setEffectsJson(json_encode($tech['effects'] ?? []));
            $techProtos[] = $t;
        }
        if (method_exists($request, 'setTechDefinitions')) {
            $request->setTechDefinitions($techProtos);
            $request->setActorTechLevels($actorTechLevels);
        }

        return $request;
    }

    public function buildProcessFieldsV7Request(array $fields, array $neighborCounts, array $neighborOffsets, array $neighbors, float $diffusionRate, float $preservationRate): ProcessFieldsV7Request
    {
        $request = new ProcessFieldsV7Request();

        // Flatten fields if multidimensional
        $flatFields = [];
        foreach ($fields as $row) {
            foreach ($row as $val) {
                $flatFields[] = (float)$val;
            }
        }

        $request->setFields($flatFields);
        $request->setNeighborCounts($neighborCounts);
        $request->setNeighborOffsets($neighborOffsets);
        $request->setNeighbors($neighbors);
        $request->setDiffusionRate($diffusionRate);
        $request->setPreservationRate($preservationRate);

        return $request;
    }

    public function buildComputeMetabolismGridRequest(array $populations, array $biomasses, array $industries, float $efficiency, float $baseEnergy): ComputeMetabolismGridRequest
    {
        $request = new ComputeMetabolismGridRequest();
        $request->setPopulations($populations);
        $request->setBiomasses($biomasses);
        $request->setIndustries($industries);
        $request->setEfficiency($efficiency);
        $request->setBaseEnergy($baseEnergy);

        return $request;
    }

    public function buildCalculateVocationAlignmentRequest(array $actorMotivation, array $targetProfile): CalculateVocationAlignmentRequest
    {
        $request = new CalculateVocationAlignmentRequest();
        $request->setActorMotivationJson(json_encode($actorMotivation));
        $request->setTargetProfileJson(json_encode($targetProfile));

        return $request;
    }

    public function buildGetCombinedGravityRequest(array $rulesets): GetCombinedGravityRequest
    {
        $request = new GetCombinedGravityRequest();
        $request->setRulesetsJson(json_encode($rulesets));

        return $request;
    }
}
