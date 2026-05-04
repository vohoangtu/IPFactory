<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Runtime\RuleVM\DslPayload;

interface RuleVmInterface
{
    /**
     * Legacy support: Evaluate and apply effects immediately.
     */
    public function evaluateAndApply(\App\Modules\World\Models\Universe $universe, ?\App\Modules\Simulation\Models\UniverseSnapshot $snapshot = null, ?DslPayload $rulesDsl = null): void;

    /**
     * Evaluate rules against world state and apply effects.
     */
    public function evaluateAndApplyWithState(WorldState $state, DslPayload $payload, int $tick, array $context = []): void;

    /**
     * Compatibility helper: Accepts string (path or raw) or DslPayload.
     */
    public function evaluateAndApplyWithDsl(WorldState $state, string|DslPayload $dslOrPayload, int $tick, array $context = []): void;

    /**
     * Evaluate rules and return result without applying it.
     */
    public function evaluate(WorldState $state, DslPayload $payload, int $tick, array $context = []): EngineResult;

    /**
     * Evaluate rules against raw state array and DSL string.
     */
    public function evaluateRawState(array $rawState, string $dsl): array;

    /**
     * Alias for evaluateRawState to support legacy services.
     */
    public function evaluateRaw(array $rawState, string $dsl): array;

    /**
     * Load a DSL payload from path or inline string.
     */
    public function loadDslPayload(string $pathOrDsl, bool $allowMutated = true): DslPayload;
}
