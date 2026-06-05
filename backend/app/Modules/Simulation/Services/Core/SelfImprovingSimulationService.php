<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Services\Core;

use App\Contracts\SimulationEngineClientInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Self-Improving Simulation Architecture (Doc §30): closed-loop rule evolution.
 *
 * Pipeline: Simulation → Metrics → AI Analysis → Rule Proposal → Sandbox Test → Score → Deploy.
 *
 * This service orchestrates the full closed loop:
 * 1. Collect metrics from the latest simulation run.
 * 2. Identify patterns that suggest a rule change.
 * 3. Propose a candidate DSL rule.
 * 4. Sandbox-test the rule against historical state.
 * 5. Score and optionally auto-deploy the rule if it passes thresholds.
 */
final class SelfImprovingSimulationService
{
    /** Minimum sandbox score to auto-deploy a rule. */
    private const AUTO_DEPLOY_THRESHOLD = 0.7;

    /** Key prefix for storing evaluation history in cache. */
    private const HISTORY_CACHE_PREFIX = 'worldos.self_improving.history.';

    public function __construct(
        protected SimulationEngineClientInterface $engine
    ) {}

    /**
     * Run the full closed-loop improvement cycle for one pattern.
     *
     * Returns a result summary suitable for logging, metrics, or dashboard display.
     */
    public function runImprovementCycle(string $patternId, array $state, array $metrics = []): array
    {
        // 1. Propose a candidate rule for the detected pattern.
        $candidate = $this->proposeRule($patternId);
        if ($candidate === null) {
            return [
                'pattern_id'   => $patternId,
                'action'       => 'skip',
                'reason'       => 'no_candidate_rule',
                'deployed'      => false,
            ];
        }

        $dsl = $candidate['dsl'] ?? '';
        if ($dsl === '') {
            return [
                'pattern_id'   => $patternId,
                'action'       => 'skip',
                'reason'       => 'empty_dsl',
                'deployed'      => false,
            ];
        }

        // 2. Sandbox-test the rule against a copy of the current state.
        $result = $this->sandboxTest($state, $dsl);

        // 3. Score the sandbox result using available metrics.
        $score = $this->scoreSandboxResult($result, $metrics);

        // 4. Record evaluation history.
        $this->recordEvaluation($patternId, $dsl, $score, $result);

        // 5. Auto-deploy if score exceeds threshold AND the global
        // auto-deploy gate is enabled. The gate defaults to OFF so a human
        // must opt in via worldos.self_improving.auto_deploy config.
        $deployed = false;
        if ($score >= self::AUTO_DEPLOY_THRESHOLD) {
            $deployed = $this->deployRule($patternId, $dsl, autoDeploy: true);
        }

        Log::info('SelfImproving: cycle completed', [
            'pattern_id' => $patternId,
            'score'      => round($score, 3),
            'deployed'   => $deployed,
            'ok'         => $result['ok'],
        ]);

        return [
            'pattern_id'    => $patternId,
            'action'        => $deployed ? 'deployed' : ($result['ok'] ? 'evaluated' : 'failed'),
            'score'         => round($score, 3),
            'deployed'      => $deployed,
            'ok'            => $result['ok'],
            'error_message'  => $result['error_message'] ?? null,
        ];
    }

    /**
     * Propose a rule: config-based (worldos.self_improving.candidate_rules[patternId]).
     *
     * @return array{dsl: string}|null
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
     * Run rule DSL against a state copy without affecting the live universe.
     *
     * @return array{ok: bool, outputs: array, error_message: string|null}
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

    /**
     * Score a sandbox result (0.0–1.0) based on outputs and metrics.
     *
     * Scoring heuristic:
     * - Base 0.5 if sandbox passed (ok=true), 0.0 if failed.
     * - +0.2 if outputs contain non-empty results (rule had effect).
     * - +0.2 if no error message (clean execution).
     * - +0.1 bonus if metrics show improvement vs baseline.
     */
    private function scoreSandboxResult(array $sandboxResult, array $metrics): float
    {
        $score = $sandboxResult['ok'] ? 0.5 : 0.0;

        $outputs = $sandboxResult['outputs'] ?? [];
        if (! empty($outputs) && ! (count($outputs) === 1 && empty($outputs[0]))) {
            $score += 0.2;
        }

        if (empty($sandboxResult['error_message'])) {
            $score += 0.2;
        }

        // Bonus if simulation metrics show improvement.
        if (! empty($metrics)) {
            $improvement = ($metrics['delta_positive'] ?? 0) - ($metrics['delta_negative'] ?? 0);
            if ($improvement > 0) {
                $score += min(0.1, $improvement * 0.01);
            }
        }

        return max(0.0, min(1.0, $score));
    }

    /**
     * Record the evaluation result so the system can learn over time.
     */
    private function recordEvaluation(string $patternId, string $dsl, float $score, array $result): void
    {
        $key = self::HISTORY_CACHE_PREFIX . $patternId;
        $entry = [
            'timestamp' => now()->toISOString(),
            'pattern_id' => $patternId,
            'dsl_preview' => mb_substr($dsl, 0, 200),
            'score' => round($score, 3),
            'ok' => $result['ok'],
            'error' => $result['error_message'] ?? null,
        ];

        $history = Cache::get($key, []);
        $history[] = $entry;

        // Keep last 50 evaluations per pattern.
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }

        Cache::put($key, $history, now()->addDays(30));
    }

    /**
     * Deploy an approved rule by storing it as the active version.
     *
     * Auto-deploy is gated by config('worldos.self_improving.auto_deploy', false)
     * — disabled by default so an operator must explicitly opt in. When the
     * gate is closed the rule is recorded as "approved but not deployed" in
     * the audit channel so a human can review and call deployRule() manually
     * if they want to ship it. Every deploy (auto or manual) writes a
     * timestamped, identifiable record to the dedicated `self_improving_audit`
     * log channel so deployments are traceable end-to-end.
     *
     * In production this would persist to a rules table; cache is acceptable
     * only as long as the audit log is the source of truth for who deployed
     * what when.
     */
    public function deployRule(string $patternId, string $dsl, bool $autoDeploy = false): bool
    {
        if ($autoDeploy && ! (bool) Config::get('worldos.self_improving.auto_deploy', false)) {
            $this->auditLog('approved_not_deployed', $patternId, $dsl, [
                'reason' => 'auto_deploy_disabled_by_config',
            ]);
            return false;
        }

        $deploymentId = $patternId . ':' . now()->timestamp . ':' . substr(bin2hex(random_bytes(4)), 0, 8);

        try {
            Cache::put(
                "worldos.self_improving.deployed.{$patternId}",
                [
                    'dsl' => $dsl,
                    'deployed_at' => now()->toISOString(),
                    'deployment_id' => $deploymentId,
                    'auto_deploy' => $autoDeploy,
                ],
                now()->addYear()
            );
            $this->auditLog('deployed', $patternId, $dsl, [
                'deployment_id' => $deploymentId,
                'auto_deploy' => $autoDeploy,
            ]);
            return true;
        } catch (\Throwable $e) {
            $this->auditLog('deploy_failed', $patternId, $dsl, [
                'deployment_id' => $deploymentId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Write to the dedicated self_improving_audit log channel if it exists,
     * falling back to the default log channel. The dedicated channel makes
     * it easy to route deployments to a separate stream, alert, or SIEM.
     */
    private function auditLog(string $event, string $patternId, string $dsl, array $context = []): void
    {
        $context = array_merge([
            'event' => $event,
            'pattern_id' => $patternId,
            'dsl_length' => strlen($dsl),
            'dsl_sha256' => hash('sha256', $dsl),
        ], $context);

        try {
            Log::channel('self_improving_audit')->info("SelfImproving: {$event}", $context);
        } catch (\Throwable) {
            // Channel not configured — fall back to default log so we never
            // lose the audit record. Operators can wire the channel later
            // in config/logging.php without changing this code.
            Log::info("SelfImproving: {$event}", $context);
        }
    }

    /**
     * Get evaluation history for a pattern.
     *
     * @return list<array{timestamp: string, score: float, ok: bool, error: string|null}>
     */
    public function getHistory(string $patternId): array
    {
        return Cache::get(self::HISTORY_CACHE_PREFIX . $patternId, []);
    }

    /**
     * Get the currently deployed rule for a pattern, if any.
     */
    public function getDeployedRule(string $patternId): ?array
    {
        return Cache::get("worldos.self_improving.deployed.{$patternId}");
    }
}
