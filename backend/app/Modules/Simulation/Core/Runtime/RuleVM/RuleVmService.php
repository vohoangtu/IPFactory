<?php

namespace App\Modules\Simulation\Core\Runtime\RuleVM;

use App\Modules\World\Models\Universe;

use App\Contracts\SimulationEngineClientInterface;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Effects\WorldStateUpdateEffect;
use App\Modules\Simulation\Services\Core\RuleMutationService;
use App\Modules\Simulation\Services\Core\CausalCacheService;
use App\Modules\Simulation\Services\Cosmology\AxiomRegistry;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Modules\Simulation\Core\Runtime\RuleVM\DslPayload;

/**
 * RuleVmService (Standardized V10): Evaluate DSL rules against world state (Rust Rule VM).
 * Functional Core: returns an EngineResult (collection of Effects).
 */
class RuleVmService implements \App\Contracts\RuleVmInterface
{
    private static array $dslFileCache = [];
    private static array $dslFileMtime = [];

    public function __construct(
        protected readonly SimulationEngineClientInterface $engine,
        protected readonly AxiomRegistry $axiomRegistry,
        protected readonly EffectExecutor $executor,
        protected readonly RuleMutationService $mutationService,
    ) {}

    /**
     * Legacy support: Evaluate and apply effects immediately.
     */
    public function evaluateAndApply(\App\Modules\World\Models\Universe $universe, ?\App\Modules\Simulation\Models\UniverseSnapshot $snapshot = null, ?DslPayload $rulesDsl = null): void
    {
        $state = app(\App\Modules\Simulation\Core\Runtime\State\StateManager::class)->get();
        if (!$state) return;

        $tick = (int) ($snapshot ? $snapshot->tick : $universe->current_tick);
        $rulesPath = Config::get('worldos.rule_engine.rules_path');
        $payload = $rulesDsl ?? ($rulesPath ? $this->loadDslPayload($rulesPath) : null);
        
        if (!$payload || $payload->isEmpty()) return;

        $result = $this->evaluate($state, $payload, $tick);
        
        $this->executor->execute((int)$universe->id, $tick, $result, $state);
    }

    public function evaluateAndApplyWithState(WorldState $state, DslPayload $payload, int $tick, array $context = []): void
    {
        if ($payload->isEmpty()) return;
        $result = $this->evaluate($state, $payload, $tick, $context);
        $universeId = (int) $state->get('universe_id');
        if ($universeId && $state instanceof \App\Modules\Simulation\Core\Runtime\State\WorldStateMutable) {
            $this->executor->execute($universeId, $tick, $result, $state);
        }
    }

    /**
     * Compatibility helper: Accepts string (path or raw) or DslPayload.
     */
    public function evaluateAndApplyWithDsl(WorldState $state, string|DslPayload $dslOrPayload, int $tick, array $context = []): void
    {
        $payload = $dslOrPayload instanceof DslPayload 
            ? $dslOrPayload 
            : $this->loadDslPayload($dslOrPayload);
            
        $this->evaluateAndApplyWithState($state, $payload, $tick, $context);
    }

    /**
     * Evaluate rules and return result without applying it.
     */
    public function evaluate(WorldState $state, DslPayload $payload, int $tick, array $context = []): EngineResult
    {
        $outputs = $this->evaluateWithResults($state, $payload, $tick, $context);
        return $this->mapOutputsToResults($outputs, (int)$state->get('universe_id'), $tick, $state);
    }

    public function evaluateRawState(array $rawState, string $dsl): array
    {
        if (empty($dsl)) return ['ok' => false, 'state' => [], 'error_message' => 'empty DSL'];
        $result = $this->engine->evaluateRules($rawState, $dsl);
        return $result ?? ['ok' => false, 'state' => []];
    }

    /**
     * Alias for evaluateRawState to support legacy services.
     */
    public function evaluateRaw(array $rawState, string $dsl): array
    {
        return $this->evaluateRawState($rawState, $dsl);
    }

    public function evaluateWithResults(WorldState $state, DslPayload $payload, int $tick, array $context = []): array
    {
        if ($payload->isEmpty()) return [];

        $dsl = $payload->getRawContent();
        $rawState = array_merge($this->buildRawStateFromManifold($state, $tick), $context);
        
        $cacheService = app(CausalCacheService::class);
        $result = $cacheService->remember($rawState, $dsl, function() use ($rawState, $dsl) {
             // Rust-compatibility: force empty arrays to objects
             $state = $rawState;
             if (isset($state['axioms']) && empty($state['axioms'])) $state['axioms'] = (object)[];
             if (isset($state['fields']) && empty($state['fields'])) $state['fields'] = (object)[];
             
             return $this->engine->evaluateRules($rawState, $dsl);
        });

        if (! ($result['ok'] ?? false)) {
            Log::warning('Rule VM evaluation failed', [
                'universe_id' => $state->get('universe_id'),
                'error' => $result['error_message'] ?? 'unknown',
            ]);
            return [];
        }

        return $result['outputs'] ?? [];
    }

    public function mapOutputsToResults(array $outputs, int $universeId, int $tick, WorldState $state): EngineResult
    {
        $events = [];
        $effects = [];

        foreach ($outputs as $out) {
            $type = $out['type'] ?? '';

            if ($type === 'event' && !empty($out['event_name'])) {
                $events[] = $out; 
            }

            if ($type === 'adjust_stability' && isset($out['adjust_stability_delta'])) {
                $current = (float) $state->get('stability_index', 1.0);
                $effects[] = new WorldStateUpdateEffect([
                    'stability_index' => max(0.0, min(1.0, $current + (float) $out['adjust_stability_delta']))
                ]);
            }

            if ($type === 'adjust_entropy' && isset($out['adjust_entropy_delta'])) {
                $current = (float) $state->get('entropy', 0.0);
                $newEntropy = max(0.0, min(1.0, $current + (float) $out['adjust_entropy_delta']));
                $effects[] = new WorldStateUpdateEffect([
                    'entropy' => $newEntropy,
                    'global_entropy' => $newEntropy
                ]);
            }

            // ... (Additional mapping for spawn_actor, drift, etc)
            if ($type === 'spawn_actor') {
                 $events[] = ['event_name' => 'SPAWN_ACTOR', 'payload' => ['kind' => $out['spawn_actor_kind'] ?? 'villager']];
            }
        }

        return new EngineResult($events, $effects, []);
    }

    public function loadDslPayload(string $pathOrDsl, bool $allowMutated = true): DslPayload
    {
        $raw = $this->loadDslRaw($pathOrDsl, $allowMutated);
        return new DslPayload($raw, [
            'source' => str_contains($pathOrDsl, "\n") ? 'inline' : 'file',
            'path' => $pathOrDsl,
            'mutated' => $allowMutated
        ]);
    }

    private function loadDslRaw(string $pathOrDsl, bool $allowMutated = true): string
    {
        // Treat as raw DSL if it contains newlines or specific DSL tokens (but don't parse!)
        if (str_contains($pathOrDsl, "\n") || str_contains($pathOrDsl, "rule ")) {
            return $pathOrDsl;
        }

        $path = $this->resolveDslFilePath($pathOrDsl);
        if ($path === null || !file_exists($path)) {
            return '';
        }

        $isProduction = app()->environment('production');
        $currentMtime = !$isProduction ? filemtime($path) : null;
        $useMutated = $allowMutated && (bool) config('worldos.autopoiesis.enabled', true);
        $cacheKey = $path . '|mutated:' . (int) $useMutated;

        if (!isset(self::$dslFileCache[$cacheKey]) || ($currentMtime !== null && $currentMtime !== (self::$dslFileMtime[$cacheKey] ?? null))) {
            $mutated = $useMutated ? $this->mutationService->getMutatedContent($pathOrDsl) : null;
            self::$dslFileCache[$cacheKey] = $mutated ?: (@file_get_contents($path) ?: '');
            self::$dslFileMtime[$cacheKey] = $currentMtime;
        }

        return self::$dslFileCache[$cacheKey];
    }

    public function resolveDslContent(string $pathOrDsl): string
    {
        return $this->loadDslRaw($pathOrDsl);
    }

    public static function clearDslCache(?string $pathOrDsl = null): void
    {
        if ($pathOrDsl === null) {
            self::$dslFileCache = [];
            self::$dslFileMtime = [];
            return;
        }

        if (str_contains($pathOrDsl, "\n") || str_contains($pathOrDsl, "rule")) {
            return;
        }

        $suffix = str_ends_with($pathOrDsl, '.dsl') ? $pathOrDsl : $pathOrDsl . '.dsl';
        $path = resource_path('worldos_rules/' . $suffix);

        foreach ([0, 1] as $mutatedFlag) {
            $cacheKey = $path . '|mutated:' . $mutatedFlag;
            unset(self::$dslFileCache[$cacheKey], self::$dslFileMtime[$cacheKey]);
        }
    }

    protected function resolveDslFilePath(string $pathOrDsl): ?string
    {
        $suffix = str_ends_with($pathOrDsl, '.dsl') ? $pathOrDsl : $pathOrDsl . '.dsl';

        return resource_path('worldos_rules/' . $suffix);
    }

    protected function buildRawStateFromManifold(WorldState $state, int $tick): array
    {
        // ... (Same mapping as before, simplified for Pure VM)
        $fields = $state->getFields();
        return [
            'tick' => $tick,
            'universe_id' => (int) $state->get('universe_id', 0),
            'entropy' => (float) $state->get('entropy', 0.5),
            'stability_index' => (float) $state->get('stability_index', 0.5),
            'fields' => $fields,
            'axioms' => $this->axiomRegistry->getDefaultMapForTier(1),
            'meta' => $state->get('meta', []),
        ];
    }
}
