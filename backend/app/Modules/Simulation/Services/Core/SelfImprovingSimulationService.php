<?php

namespace App\Modules\Simulation\Services\Core;

use App\Contracts\SimulationEngineClientInterface;
use Illuminate\Support\Facades\Config;

/**
 * Self-Improving Simulation Architecture (Doc §30): closed loop placeholder.
 * Simulation → Data → AI Analysis → Rule Proposal → Sandbox Test → Deploy.
 * Phase 3: proposeRule config-based; sandboxTest used by handler.
 */
final class SelfImprovingSimulationService
{
    public function __construct(
        protected SimulationEngineClientInterface $engine
    ) {}

    /**
     * Propose a rule: config-based (worldos.self_improving.candidate_rules[patternId]).
     * Returns ['dsl' => '...'] or null if no candidate for patternId.
     */
    public function proposeRule(string $patternId): ?array
    {
        $candidates = Config::get('worldos.self_improving.candidate_rules', []);
        $dsl = $candidates[$patternId] ?? null;
        if ($dsl === null || $dsl === '') {
            return null;
        }
        if (is_array($dsl) && isset($dsl['dsl'])) {
            return $dsl;
        }
        return ['dsl' => (string) $dsl];
    }

    /**
     * Run rule DSL against a state copy without applying to universe.
     * Returns engine evaluate-rules result (ok, outputs, error_message).
     */
    public function sandboxTest(array $state, string $rulesDsl): array
    {
        $result = $this->engine->evaluateRules($state, $rulesDsl);

        return [
            'ok' => $result['ok'] ?? false,
            'outputs' => $result['outputs'] ?? [],
            'error_message' => $result['error_message'] ?? null,
        ];
    }
}
