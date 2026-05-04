<?php

namespace App\Modules\Simulation\Vocation\DSL;

use App\Contracts\SimulationEngineClientInterface;
use Illuminate\Support\Facades\Log;

/**
 * ExpressionEngine: Proxy to Rust Rule VM for Skill/Vocation calculations.
 * V1: Encapsulates gRPC calls for rule evaluation.
 */
class ExpressionEngine
{
    public function __construct(
        private SimulationEngineClientInterface $client
    ) {}

    /**
     * Evaluate a skill rule against a provided context using Rust Rule VM.
     *
     * @param string $dsl The DSL rule script (e.g., "rule fireball then calc damage ...").
     * @param ExpressionContext $context The combined actor/target/world state.
     * @return array Raw outputs from the Rust engine.
     */
    public function evaluate(string $dsl, ExpressionContext $context): array
    {
        if (empty($dsl)) {
            return [];
        }

        $state = $context->toArray();

        // Standardized gRPC call to evaluate rules
        $result = $this->client->evaluateRules($state, $dsl);

        if (!$result['ok']) {
            Log::error("Vocation ExpressionEngine gRPC error: " . ($result['error_message'] ?? 'Unknown error'));
            return [];
        }

        return $result['outputs'] ?? [];
    }
}
