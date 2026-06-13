<?php

namespace App\Modules\Narrative\Services;
use App\Contracts\StateExtractorDslInterface;

use App\Modules\Simulation\Core\Support\RuleEngine;

/**
 * Encapsulates the logic to extract "Narrative Tokens" from raw simulation data.
 * Replaces the old fragmented PerceivedArchiveBuilder.
 */
class StateExtractorDSL implements StateExtractorDslInterface
{
    public function __construct(
        protected RuleEngine $ruleEngine
    ) {}

    /**
     * @param array $stateVector Raw state vector
     * @param array $metrics Aggregated simulation metrics
     * @return array Object containing both high-level tokens and detailed causal events.
     */
    public function extractContext(int $universeId, int $tick, array $stateVector, array $metrics = []): array
    {
        $tokens = $this->extract($stateVector, $metrics);
        
        /** @var NarrativeEventRegistry $registry */
        $registry = app(NarrativeEventRegistry::class);
        
        // Extract events for the last few ticks to provide causal context
        $events = $registry->getEventsForContext($universeId, max(0, $tick - 5), $tick);

        return [
            'tokens' => $tokens,
            'events' => $events->toArray(),
            'timestamp' => [
                'universe_id' => $universeId,
                'tick' => $tick
            ]
        ];
    }

    public function extract(array $stateVector, array $metrics = []): array
    {
        $tokens = [];
        $ruleDefinitions = $this->getRuleDefinitions();

        // Resolver uses Laravel's data_get to support dotted notation (e.g., "metrics.social.conflict")
        $resolver = function (string $key) use ($stateVector, $metrics) {
            $context = array_merge($stateVector, ['metrics' => $metrics]);
            return data_get($context, $key);
        };

        foreach ($ruleDefinitions as $token => $rules) {
            if ($this->ruleEngine->evaluate($rules, [], $resolver)) {
                $tokens[] = $token;
            }
        }

        return $tokens;
    }

    /**
     * Defined extraction rules from config.
     */
    protected function getRuleDefinitions(): array
    {
        return config('worldos_narrative.rules', []);
    }
}

