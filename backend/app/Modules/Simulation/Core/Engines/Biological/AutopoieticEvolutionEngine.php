<?php

namespace App\Modules\Simulation\Core\Engines\Biological;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Services\Core\RuleMutationService;
use Illuminate\Support\Facades\Log;

/**
 * Phase 74: Autopoietic Evolution Engine (Self-Modifying Logic) 🧬💻
 *
 * "Mã nguồn không còn là hằng số, nó là một thực thể sống."
 * Thực hiện đột biến thực sự vào các file DSL dựa trên áp lực thực tại.
 */
class AutopoieticEvolutionEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    public function __construct(
        protected RuleMutationService $mutationService,
        protected RuleVmService $ruleVmService,
    ) {}

    public function name(): string
    {
        return 'autopoietic_evolution';
    }

    public function phase(): string
    {
        return 'biological';
    }

    public function priority(): int
    {
        return 99; // Runs last or near last
    }

    public function tickRate(): int
    {
        return (int) config('worldos.autopoiesis.tick_interval', 100);
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        if (!config('worldos.autopoiesis.enabled', true)) {
            return EngineResult::empty();
        }

        $targets = [
            'simulation/physics',
            'simulation/integrity',
            'simulation/autopoiesis',
            'biology/biosphere',
            'simulation/consciousness',
        ];

        $events = [];
        foreach ($targets as $dsl) {
            $result = $this->evolveLogic($dsl, $state);
            if ($result['changed'] ?? false) {
                $events[] = \App\Modules\Simulation\Core\Events\WorldEvent::create(
                    'AUTOPOIESIS_MUTATION',
                    (int) $state->get('universe_id'),
                    $ctx->getTick(),
                    null,
                    [],
                    1.0,
                    [],
                    $result
                );
            }
        }

        return new EngineResult($events, [], []);
    }

    /**
     * Evolve a specific DSL logic based on reality pressure.
     */
    public function evolveLogic(string $dslPath, WorldState $state): array
    {
        $entropy = (float) $state->get('entropy', 0.5);
        $threshold = (float) config('worldos.autopoiesis.entropy_threshold', 0.70);
        $density = (float) $state->get('meta.information_density', 0.0);
        $universeId = (int) $state->get('universe_id');

        // 1. Load existing mutated or original content
        $content = $this->ruleVmService->resolveDslContent($dslPath);

        if (!$content) {
            return ['error' => "Source DSL not found: $dslPath", 'path' => $dslPath, 'changed' => false];
        }

        $original = $content;
        $mutationVector = null;

        // 2. Entropy Spike → inject stabilization guard
        if ($entropy > $threshold && !str_contains($content, '# autopoiesis_stabilize')) {
            $content .= "\n# autopoiesis_stabilize (injected)\ndrift 'entropy' by -0.05 if entropy > " . ($threshold + 0.05);
            $mutationVector = 'Entropy Management';
        }

        // 3. Singularity pressure → halve physics drift increments
        if ($density > 0.9 && !str_contains($content, '# autopoiesis_optimize')) {
            $content = preg_replace(
                "/(drift '[\w.]+' by )([\d.]+)(?!\s*#\s*autopoiesis_optimize)/",
                '$1' . '$2 * 0.5 # autopoiesis_optimize',
                $content,
                5 // limit replacements
            );
            $mutationVector = ($mutationVector ?? '') . ' Complexity Optimization';
        }

        // 4. Stability collapse → reset observation load
        $stability = (float) $state->get('stability_index', 1.0);
        if ($stability < 0.25 && !str_contains($content, '# autopoiesis_reset_obs')) {
            $content .= "\n# autopoiesis_reset_obs\nset 'meta.observation_load' to 0.0";
            $mutationVector = ($mutationVector ?? '') . ' Observer Reset';
        }

        // 5. Persist only if changed
        if ($content !== $original && $mutationVector) {
            $this->mutationService->applyMutation($dslPath, $content, [
                'universe_id' => $universeId,
                'tick' => (int) $state->get('tick', 0),
                'entropy' => $entropy,
                'density' => $density,
                'stability' => $stability,
                'vector' => trim($mutationVector),
                'source' => 'autopoietic_evolution',
            ]);

            Log::info("Autopoiesis: Evolved [{$dslPath}] | Vector: {$mutationVector}", compact('universeId', 'entropy', 'density'));

            return [
                'path' => $dslPath,
                'vector' => trim($mutationVector),
                'changed' => true,
                'timestamp' => now()->toIso8601String(),
            ];
        }

        return [
            'path' => $dslPath,
            'changed' => false,
        ];
    }
}
