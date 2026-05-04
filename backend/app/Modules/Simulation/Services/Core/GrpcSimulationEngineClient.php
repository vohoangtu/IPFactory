<?php

namespace App\Modules\Simulation\Services\Core;

use App\Contracts\SimulationEngineClientInterface;
use App\Modules\Simulation\Services\Core\Grpc\GrpcConnectionManager;
use App\Modules\Simulation\Services\Core\Grpc\GrpcRequestBuilder;
use App\Modules\Simulation\Services\Core\Grpc\GrpcResponseParser;

/**
 * gRPC bridge to WorldOS simulation engine (Rust).
 * Implements SimulationEngineClientInterface using generated Protobuf classes.
 *
 * Delegates to:
 *  - GrpcConnectionManager: connection lifecycle, credentials, timeout options
 *  - GrpcRequestBuilder: protobuf message construction
 *  - GrpcResponseParser: response deserialization, error mapping
 */
class GrpcSimulationEngineClient implements SimulationEngineClientInterface
{
    private GrpcConnectionManager $connectionManager;
    private GrpcRequestBuilder $requestBuilder;
    private GrpcResponseParser $responseParser;

    public function __construct(string $hostname)
    {
        $this->connectionManager = new GrpcConnectionManager($hostname);
        $this->requestBuilder = new GrpcRequestBuilder();
        $this->responseParser = new GrpcResponseParser();
    }

    public function advance(int $universeId, int $ticks, array $stateInput = [], ?array $worldConfig = null): array
    {
        $request = $this->requestBuilder->buildAdvanceRequest($universeId, $ticks, $stateInput, $worldConfig);

        list($response, $status) = $this->connectionManager->getClient()
            ->Advance($request, [], $this->connectionManager->getOptions())
            ->wait();

        return $this->responseParser->parseAdvanceResponse($response, $status);
    }

    public function merge(string $stateA, string $stateB): array
    {
        $request = $this->requestBuilder->buildMergeRequest($stateA, $stateB);

        list($response, $status) = $this->connectionManager->getClient()
            ->Merge($request, [], $this->connectionManager->getOptions())
            ->wait();

        return $this->responseParser->parseMergeResponse($response, $status);
    }

    public function batchAdvance(array $requests): array
    {
        $batchRequest = $this->requestBuilder->buildBatchAdvanceRequest($requests);

        list($response, $status) = $this->connectionManager->getClient()
            ->BatchAdvance($batchRequest, [], $this->connectionManager->getOptions(10000))
            ->wait();

        return $this->responseParser->parseBatchAdvanceResponse($response, $status);
    }

    public function analyzeTrajectory(array $points, float $threshold = 0.1): array
    {
        $request = $this->requestBuilder->buildTrajectoryAnalysisRequest($points, $threshold);

        list($response, $status) = $this->connectionManager->getClient()
            ->AnalyzeTrajectory($request, [], $this->connectionManager->getOptions())
            ->wait();

        return $this->responseParser->parseTrajectoryAnalysisResponse($response, $status);
    }

    public function evaluateRules(array $state, ?string $rulesDsl = null): array
    {
        $request = $this->requestBuilder->buildEvaluateRulesRequest($state, $rulesDsl);

        list($response, $status) = $this->connectionManager->getClient()
            ->EvaluateRules($request, [], $this->connectionManager->getOptions())
            ->wait();

        return $this->responseParser->parseEvaluateRulesResponse($response, $status);
    }

    public function processActorsSoa(
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
    ): array {
        $request = $this->requestBuilder->buildProcessActorsSoaRequest(
            $tick,
            $ids,
            $zoneIds,
            $hunger,
            $energy,
            $fear,
            $trauma,
            $heroicTypes,
            $lineageIds,
            $memes,
            $traitsMatrix,
            $behaviorStates,
            $behaviorGraphs,
            $archetypes,
            $socialGraph,
            $edicts,
            $factionIds,
            $factionLoyalty,
            $isObserved,
            $narrativeContext,
            $factionRelations,
            $beliefDefinitions,
            $beliefAlignments,
            $techDefinitions,
            $actorTechLevels
        );

        list($response, $status) = $this->connectionManager->getClient()
            ->ProcessActorsSoa($request, [], $this->connectionManager->getOptions())
            ->wait();

        return $this->responseParser->parseProcessActorsSoaResponse($response, $status);
    }

    public function processFieldsV7(array $fields, array $neighborCounts, array $neighborOffsets, array $neighbors, float $diffusionRate, float $preservationRate): array
    {
        $request = $this->requestBuilder->buildProcessFieldsV7Request($fields, $neighborCounts, $neighborOffsets, $neighbors, $diffusionRate, $preservationRate);

        list($response, $status) = $this->connectionManager->getClient()
            ->ProcessFieldsV7($request, [], $this->connectionManager->getOptions())
            ->wait();

        return $this->responseParser->parseProcessFieldsV7Response($response, $status, $fields);
    }

    public function computeMetabolismGrid(array $populations, array $biomasses, array $industries, float $efficiency, float $baseEnergy): array
    {
        $request = $this->requestBuilder->buildComputeMetabolismGridRequest($populations, $biomasses, $industries, $efficiency, $baseEnergy);

        list($response, $status) = $this->connectionManager->getClient()
            ->ComputeMetabolismGrid($request, [], $this->connectionManager->getOptions())
            ->wait();

        return $this->responseParser->parseComputeMetabolismGridResponse($response, $status);
    }

    public function calculateVocationAlignment(array $actorMotivation, array $targetProfile): float
    {
        $request = $this->requestBuilder->buildCalculateVocationAlignmentRequest($actorMotivation, $targetProfile);

        list($response, $status) = $this->connectionManager->getClient()
            ->CalculateVocationAlignment($request, [], $this->connectionManager->getOptions(2000))
            ->wait();

        return $this->responseParser->parseCalculateVocationAlignmentResponse($response, $status);
    }

    public function getCombinedGravity(array $rulesets): float
    {
        $request = $this->requestBuilder->buildGetCombinedGravityRequest($rulesets);

        list($response, $status) = $this->connectionManager->getClient()
            ->GetCombinedGravity($request, [], $this->connectionManager->getOptions(2000))
            ->wait();

        return $this->responseParser->parseGetCombinedGravityResponse($response, $status);
    }
}
