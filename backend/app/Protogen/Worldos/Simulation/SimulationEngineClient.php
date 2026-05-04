<?php

namespace Worldos\Simulation;

use Grpc\BaseStub;

class SimulationEngineClient extends BaseStub
{
    public function __construct($hostname, $opts, $channel = null)
    {
        parent::__construct($hostname, $opts, $channel);
    }

    public function Advance(AdvanceRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest(
            '/worldos.simulation.SimulationEngine/Advance',
            $argument,
            ['\Worldos\Simulation\AdvanceResponse', 'decode'],
            $metadata,
            $options
        );
    }

    public function Merge(MergeRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest(
            '/worldos.simulation.SimulationEngine/Merge',
            $argument,
            ['\Worldos\Simulation\MergeResponse', 'decode'],
            $metadata,
            $options
        );
    }

    public function Observe(ObserveRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest(
            '/worldos.simulation.SimulationEngine/Observe',
            $argument,
            ['\Worldos\Simulation\ObserveResponse', 'decode'],
            $metadata,
            $options
        );
    }

    public function BatchAdvance(BatchAdvanceRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest(
            '/worldos.simulation.SimulationEngine/BatchAdvance',
            $argument,
            ['\Worldos\Simulation\BatchAdvanceResponse', 'decode'],
            $metadata,
            $options
        );
    }

    public function AnalyzeTrajectory(TrajectoryAnalysisRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest(
            '/worldos.simulation.SimulationEngine/AnalyzeTrajectory',
            $argument,
            ['\Worldos\Simulation\TrajectoryAnalysisResponse', 'decode'],
            $metadata,
            $options
        );
    }

    public function EvaluateRules(EvaluateRulesRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest(
            '/worldos.simulation.SimulationEngine/EvaluateRules',
            $argument,
            ['\Worldos\Simulation\EvaluateRulesResponse', 'decode'],
            $metadata,
            $options
        );
    }

    public function ProcessActorsSoa(ProcessActorsSoaRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest(
            '/worldos.simulation.SimulationEngine/ProcessActorsSoa',
            $argument,
            ['\Worldos\Simulation\ProcessActorsSoaResponse', 'decode'],
            $metadata,
            $options
        );
    }

    public function ProcessFieldsV7(ProcessFieldsV7Request $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest(
            '/worldos.simulation.SimulationEngine/ProcessFieldsV7',
            $argument,
            ['\Worldos\Simulation\ProcessFieldsV7Response', 'decode'],
            $metadata,
            $options
        );
    }

    public function ComputeMetabolismGrid(ComputeMetabolismGridRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest(
            '/worldos.simulation.SimulationEngine/ComputeMetabolismGrid',
            $argument,
            ['\Worldos\Simulation\ComputeMetabolismGridResponse', 'decode'],
            $metadata,
            $options
        );
    }

    public function CalculateVocationAlignment(CalculateVocationAlignmentRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest(
            '/worldos.simulation.SimulationEngine/CalculateVocationAlignment',
            $argument,
            ['\Worldos\Simulation\CalculateVocationAlignmentResponse', 'decode'],
            $metadata,
            $options
        );
    }

    public function GetCombinedGravity(GetCombinedGravityRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest(
            '/worldos.simulation.SimulationEngine/GetCombinedGravity',
            $argument,
            ['\Worldos\Simulation\GetCombinedGravityResponse', 'decode'],
            $metadata,
            $options
        );
    }
}
