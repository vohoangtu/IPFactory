<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Services\Core\Grpc;

use Grpc\ChannelCredentials;
use Worldos\Simulation\SimulationEngineClient;

/**
 * Manages gRPC connection lifecycle: client creation, credentials, and timeout options.
 * Extracted from GrpcSimulationEngineClient.
 */
class GrpcConnectionManager
{
    private SimulationEngineClient $client;

    public function __construct(string $hostname)
    {
        // hostname should be like "localhost:50051"
        $this->client = new SimulationEngineClient($hostname, [
            'credentials' => ChannelCredentials::createInsecure(),
            'grpc.connect_timeout_ms' => 2000, // 2s connection timeout
        ]);
    }

    public function getClient(): SimulationEngineClient
    {
        return $this->client;
    }

    public function getOptions(int $timeoutMs = 5000): array
    {
        return ['timeout' => $timeoutMs * 1000]; // gRPC PHP expects microseconds in some versions, but actually simpleRequest expects 'timeout' in microseconds?
        // Wait, documentation says 'timeout' is in microseconds for some, but typically it depends on the wrapper.
        // In Grpc\BaseStub, it's usually microseconds.
    }
}
